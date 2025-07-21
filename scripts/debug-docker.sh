#!/bin/bash

set -e

# Docker Debug Script for Regulação List
# Helps troubleshoot Docker build and registry issues

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo ""
    echo "=================================="
    echo "$1"
    echo "=================================="
}

# Default values
REGISTRY="ghcr.io"
IMAGE_NAME=""
CHECK_REMOTE=false
BUILD_TEST=false
CLEANUP=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --registry)
            REGISTRY="$2"
            shift 2
            ;;
        --image)
            IMAGE_NAME="$2"
            shift 2
            ;;
        --remote)
            CHECK_REMOTE=true
            shift
            ;;
        --build-test)
            BUILD_TEST=true
            shift
            ;;
        --cleanup)
            CLEANUP=true
            shift
            ;;
        -h|--help)
            echo "Docker Debug Script for Regulação List"
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --registry REGISTRY  Docker registry to check [default: ghcr.io]"
            echo "  --image IMAGE        Image name to check [default: auto-detect from git]"
            echo "  --remote             Check remote registry accessibility"
            echo "  --build-test         Test local build process"
            echo "  --cleanup            Clean up test resources"
            echo "  -h, --help           Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                                    # Basic local checks"
            echo "  $0 --remote                          # Check remote registry"
            echo "  $0 --build-test                      # Test build process"
            echo "  $0 --image myuser/myrepo --remote    # Check specific image"
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Auto-detect image name if not provided
if [ -z "$IMAGE_NAME" ]; then
    if git remote -v >/dev/null 2>&1; then
        REMOTE_URL=$(git remote get-url origin 2>/dev/null || echo "")
        if [[ $REMOTE_URL =~ github\.com[:/]([^/]+)/([^/]+)(\.git)?$ ]]; then
            OWNER="${BASH_REMATCH[1]}"
            REPO="${BASH_REMATCH[2]}"
            IMAGE_NAME="$OWNER/$REPO"
            print_status "Auto-detected image name: $IMAGE_NAME"
        fi
    fi

    if [ -z "$IMAGE_NAME" ]; then
        print_error "Could not auto-detect image name. Please specify with --image"
        exit 1
    fi
fi

FULL_IMAGE="$REGISTRY/$IMAGE_NAME"

print_header "Docker Debug Report for $FULL_IMAGE"

# System Information
print_header "System Information"

print_status "Checking Docker installation..."
if command -v docker >/dev/null 2>&1; then
    DOCKER_VERSION=$(docker --version)
    print_success "Docker found: $DOCKER_VERSION"
else
    print_error "Docker not found. Please install Docker."
    exit 1
fi

print_status "Checking Docker daemon..."
if docker info >/dev/null 2>&1; then
    print_success "Docker daemon is running"
    DOCKER_INFO=$(docker info --format "{{.ServerVersion}}")
    print_status "Docker daemon version: $DOCKER_INFO"
else
    print_error "Docker daemon is not running. Please start Docker."
    exit 1
fi

print_status "Checking Docker Buildx..."
if docker buildx version >/dev/null 2>&1; then
    BUILDX_VERSION=$(docker buildx version)
    print_success "Docker Buildx available: $BUILDX_VERSION"
else
    print_warning "Docker Buildx not available. Some features may not work."
fi

# Local Image Information
print_header "Local Docker Images"

print_status "Checking for local images matching '$IMAGE_NAME'..."
LOCAL_IMAGES=$(docker images --format "table {{.Repository}}:{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}" | grep "$IMAGE_NAME" || echo "")

if [ -n "$LOCAL_IMAGES" ]; then
    print_success "Found local images:"
    echo "$LOCAL_IMAGES"
else
    print_warning "No local images found matching '$IMAGE_NAME'"
fi

print_status "Disk usage summary..."
docker system df

# Build Context Analysis
print_header "Build Context Analysis"

if [ -f "Dockerfile" ]; then
    print_success "Dockerfile found"

    # Check Dockerfile syntax
    print_status "Analyzing Dockerfile..."
    if docker run --rm -i hadolint/hadolint < Dockerfile >/dev/null 2>&1; then
        print_success "Dockerfile syntax is valid"
    else
        print_warning "Dockerfile has linting issues (run hadolint for details)"
    fi

    # Show Dockerfile stages
    STAGES=$(grep -i "^FROM.*AS" Dockerfile | awk '{print $NF}' || echo "")
    if [ -n "$STAGES" ]; then
        print_status "Multi-stage build detected. Stages: $STAGES"
    fi
else
    print_error "Dockerfile not found in current directory"
fi

if [ -f ".dockerignore" ]; then
    print_success ".dockerignore found"
    IGNORE_LINES=$(wc -l < .dockerignore)
    print_status ".dockerignore has $IGNORE_LINES patterns"
else
    print_warning ".dockerignore not found. Consider creating one to reduce build context"
fi

print_status "Calculating build context size..."
CONTEXT_SIZE=$(du -sh . | cut -f1)
print_status "Build context size: $CONTEXT_SIZE"

if [ -f ".dockerignore" ]; then
    print_status "Calculating effective context size (after .dockerignore)..."
    # Create a temporary tar to see what would actually be sent
    TEMP_TAR=$(mktemp)
    tar -czf "$TEMP_TAR" --exclude-from=.dockerignore . 2>/dev/null || true
    EFFECTIVE_SIZE=$(du -sh "$TEMP_TAR" | cut -f1)
    print_status "Effective context size: $EFFECTIVE_SIZE"
    rm -f "$TEMP_TAR"
fi

# Remote Registry Check
if [ "$CHECK_REMOTE" = true ]; then
    print_header "Remote Registry Check"

    print_status "Checking registry accessibility: $REGISTRY"
    if curl -fsSL "https://$REGISTRY/v2/" >/dev/null 2>&1; then
        print_success "Registry is accessible"
    else
        print_error "Registry is not accessible or requires authentication"
    fi

    print_status "Checking if image exists: $FULL_IMAGE"

    # Try different tags
    for TAG in "latest" "main" "develop"; do
        print_status "Checking tag: $TAG"
        if docker manifest inspect "$FULL_IMAGE:$TAG" >/dev/null 2>&1; then
            print_success "Image found: $FULL_IMAGE:$TAG"

            # Get image details
            SIZE=$(docker manifest inspect "$FULL_IMAGE:$TAG" | jq -r '.config.size // "unknown"' 2>/dev/null || echo "unknown")
            print_status "Image size: $SIZE bytes"
        else
            print_warning "Image not found: $FULL_IMAGE:$TAG"
        fi
    done

    # List available tags if possible
    print_status "Attempting to list available tags..."
    if command -v skopeo >/dev/null 2>&1; then
        if skopeo list-tags "docker://$FULL_IMAGE" 2>/dev/null; then
            print_success "Tags listed successfully"
        else
            print_warning "Could not list tags (may require authentication)"
        fi
    else
        print_warning "skopeo not available. Cannot list tags."
    fi
fi

# Build Test
if [ "$BUILD_TEST" = true ]; then
    print_header "Build Test"

    if [ ! -f "Dockerfile" ]; then
        print_error "Cannot run build test: Dockerfile not found"
    else
        print_status "Testing Docker build..."

        TEST_TAG="$IMAGE_NAME:debug-test"
        START_TIME=$(date +%s)

        if docker build -t "$TEST_TAG" . 2>&1 | tee /tmp/docker-build.log; then
            END_TIME=$(date +%s)
            DURATION=$((END_TIME - START_TIME))
            print_success "Build completed in ${DURATION} seconds"

            # Check image size
            IMAGE_SIZE=$(docker images --format "{{.Size}}" "$TEST_TAG")
            print_status "Built image size: $IMAGE_SIZE"

            # Test image can run
            print_status "Testing if image can start..."
            if docker run --rm -d --name "$IMAGE_NAME-test" "$TEST_TAG" >/dev/null 2>&1; then
                sleep 2
                if docker ps | grep -q "$IMAGE_NAME-test"; then
                    print_success "Image started successfully"
                    docker stop "$IMAGE_NAME-test" >/dev/null 2>&1
                else
                    print_warning "Image started but stopped immediately"
                fi
            else
                print_warning "Image failed to start"
            fi

        else
            print_error "Build failed. Check /tmp/docker-build.log for details"
            cat /tmp/docker-build.log
        fi
    fi
fi

# Build Cache Analysis
print_header "Build Cache Analysis"

print_status "Checking build cache usage..."
if docker buildx du >/dev/null 2>&1; then
    docker buildx du
else
    print_warning "Cannot check build cache (buildx not available or no cache)"
fi

print_status "Docker system information..."
docker system df

# GitHub Actions Environment Check
print_header "CI/CD Environment Check"

if [ -n "$GITHUB_ACTIONS" ]; then
    print_status "Running in GitHub Actions environment"
    print_status "GitHub Actor: ${GITHUB_ACTOR:-not set}"
    print_status "GitHub Repository: ${GITHUB_REPOSITORY:-not set}"
    print_status "GitHub Ref: ${GITHUB_REF:-not set}"
    print_status "GitHub Event: ${GITHUB_EVENT_NAME:-not set}"
else
    print_status "Not running in GitHub Actions"
fi

# Environment Variables Check
print_status "Relevant environment variables:"
env | grep -E "(DOCKER|GITHUB|REGISTRY)" | sort || echo "None found"

# Recommendations
print_header "Recommendations"

echo "Based on the analysis:"
echo ""

if [ ! -f ".dockerignore" ]; then
    echo "📝 Create a .dockerignore file to reduce build context size"
fi

if [[ "$CONTEXT_SIZE" =~ [0-9]+G ]]; then
    echo "⚠️  Build context is very large ($CONTEXT_SIZE). Consider optimizing .dockerignore"
fi

if [ "$CHECK_REMOTE" = true ] && ! docker manifest inspect "$FULL_IMAGE:latest" >/dev/null 2>&1; then
    echo "🐳 Image not found in registry. Build and push first:"
    echo "   docker build -t $FULL_IMAGE:latest ."
    echo "   docker push $FULL_IMAGE:latest"
fi

echo "🚀 Use the fast build script for optimized builds:"
echo "   ./scripts/fast-build.sh"

echo "📖 Check Docker optimization guide:"
echo "   cat DOCKER_OPTIMIZATION.md"

# Cleanup
if [ "$CLEANUP" = true ]; then
    print_header "Cleanup"

    print_status "Cleaning up test resources..."

    # Remove test images
    docker rmi "$IMAGE_NAME:debug-test" >/dev/null 2>&1 || true

    # Clean up dangling images
    docker image prune -f >/dev/null 2>&1 || true

    # Clean up build cache if too large
    CACHE_SIZE=$(docker system df --format "table {{.Type}}\t{{.Size}}" | grep "Build Cache" | awk '{print $3}' || echo "0B")
    if [[ "$CACHE_SIZE" =~ [0-9]+G ]]; then
        print_status "Build cache is large ($CACHE_SIZE). Cleaning..."
        docker buildx prune -f >/dev/null 2>&1 || true
    fi

    print_success "Cleanup completed"
fi

print_header "Debug Report Complete"

echo "For more detailed analysis:"
echo "  - Check Docker logs: docker logs <container>"
echo "  - Analyze layers: docker history <image>"
echo "  - Monitor builds: docker build --progress=plain"
echo "  - Security scan: docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy image <image>"
echo ""
echo "Healthcare-specific considerations:"
echo "  - Ensure no patient data in build context"
echo "  - Use multi-stage builds for smaller images"
echo "  - Regularly scan for security vulnerabilities"
echo "  - Follow LGPD compliance for container logs"
