#!/bin/bash

set -e

# Fast Docker Build Script for Laravel App
# Optimized for speed with parallel builds and caching

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

# Default values
TARGET="production"
PLATFORM="linux/amd64"
CACHE_TYPE="local"
PUSH=false
TAG="regulacao-list:latest"
PARALLEL_BUILD=true

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -t|--target)
            TARGET="$2"
            shift 2
            ;;
        -p|--platform)
            PLATFORM="$2"
            shift 2
            ;;
        --tag)
            TAG="$2"
            shift 2
            ;;
        --push)
            PUSH=true
            shift
            ;;
        --no-cache)
            CACHE_TYPE="none"
            shift
            ;;
        --registry-cache)
            CACHE_TYPE="registry"
            shift
            ;;
        --no-parallel)
            PARALLEL_BUILD=false
            shift
            ;;
        -h|--help)
            echo "Fast Docker Build Script"
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  -t, --target TARGET      Build target (development|production) [default: production]"
            echo "  -p, --platform PLATFORM Target platform [default: linux/amd64]"
            echo "  --tag TAG               Docker image tag [default: regulacao-list:latest]"
            echo "  --push                  Push image to registry"
            echo "  --no-cache              Disable build cache"
            echo "  --registry-cache        Use registry cache instead of local"
            echo "  --no-parallel           Disable parallel builds"
            echo "  -h, --help              Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                                    # Build production image"
            echo "  $0 -t development                    # Build development image"
            echo "  $0 --platform linux/arm64           # Build for ARM64"
            echo "  $0 --push --registry-cache           # Build and push with registry cache"
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Check if Docker is running
if ! docker info >/dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker first."
    exit 1
fi

# Check if Docker Buildx is available
if ! docker buildx version >/dev/null 2>&1; then
    print_error "Docker Buildx is not available. Please install Docker Desktop or enable Buildx."
    exit 1
fi

print_status "Starting fast Docker build..."
print_status "Target: $TARGET"
print_status "Platform: $PLATFORM"
print_status "Tag: $TAG"
print_status "Cache: $CACHE_TYPE"

# Create buildx builder if it doesn't exist
BUILDER_NAME="regulacao-fast-builder"
if ! docker buildx inspect $BUILDER_NAME >/dev/null 2>&1; then
    print_status "Creating Buildx builder..."
    docker buildx create --name $BUILDER_NAME --driver docker-container --bootstrap
fi

docker buildx use $BUILDER_NAME

# Prepare cache arguments
CACHE_FROM_ARGS=""
CACHE_TO_ARGS=""

case $CACHE_TYPE in
    "local")
        CACHE_FROM_ARGS="--cache-from type=local,src=/tmp/.buildx-cache-$TARGET"
        CACHE_TO_ARGS="--cache-to type=local,dest=/tmp/.buildx-cache-$TARGET,mode=max"
        ;;
    "registry")
        REGISTRY_CACHE="$TAG-buildcache"
        CACHE_FROM_ARGS="--cache-from type=registry,ref=$REGISTRY_CACHE"
        CACHE_TO_ARGS="--cache-to type=registry,ref=$REGISTRY_CACHE,mode=max"
        ;;
    "none")
        print_warning "Cache disabled"
        ;;
esac

# Build command
BUILD_CMD="docker buildx build"
BUILD_CMD="$BUILD_CMD --platform $PLATFORM"
BUILD_CMD="$BUILD_CMD --target $TARGET"
BUILD_CMD="$BUILD_CMD --tag $TAG"

if [ "$CACHE_TYPE" != "none" ]; then
    BUILD_CMD="$BUILD_CMD $CACHE_FROM_ARGS $CACHE_TO_ARGS"
fi

# Enable inline cache for better performance
BUILD_CMD="$BUILD_CMD --build-arg BUILDKIT_INLINE_CACHE=1"

# Add parallel build optimization
if [ "$PARALLEL_BUILD" = true ]; then
    BUILD_CMD="$BUILD_CMD --build-arg BUILDKIT_CONTEXT_KEEP_GIT_DIR=1"
fi

# Load or push
if [ "$PUSH" = true ]; then
    BUILD_CMD="$BUILD_CMD --push"
else
    BUILD_CMD="$BUILD_CMD --load"
fi

BUILD_CMD="$BUILD_CMD ."

# Pre-build optimizations
print_status "Running pre-build optimizations..."

# Clean up old build cache if it's getting too large
if [ -d "/tmp/.buildx-cache-$TARGET" ]; then
    CACHE_SIZE=$(du -sm "/tmp/.buildx-cache-$TARGET" | cut -f1)
    if [ "$CACHE_SIZE" -gt 2048 ]; then  # If cache > 2GB
        print_warning "Build cache is large (${CACHE_SIZE}MB), cleaning up..."
        rm -rf "/tmp/.buildx-cache-$TARGET"
    fi
fi

# Create cache directory
mkdir -p "/tmp/.buildx-cache-$TARGET"

# Show build context size
CONTEXT_SIZE=$(du -sh . | cut -f1)
print_status "Build context size: $CONTEXT_SIZE"

# Show what files will be excluded
if [ -f .dockerignore ]; then
    IGNORED_COUNT=$(wc -l < .dockerignore)
    print_status "Using .dockerignore ($IGNORED_COUNT patterns)"
fi

# Start build with timing
print_status "Starting Docker build..."
START_TIME=$(date +%s)

echo "Running: $BUILD_CMD"
eval $BUILD_CMD

# Calculate build time
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
MINUTES=$((DURATION / 60))
SECONDS=$((DURATION % 60))

print_success "Build completed in ${MINUTES}m ${SECONDS}s"

# Show image size
if [ "$PUSH" = false ]; then
    IMAGE_SIZE=$(docker images --format "table {{.Repository}}:{{.Tag}}\t{{.Size}}" | grep "$TAG" | awk '{print $2}')
    print_success "Image size: $IMAGE_SIZE"

    # Show layers for optimization hints
    print_status "Image layers:"
    docker history "$TAG" --format "table {{.CreatedBy}}\t{{.Size}}" | head -10
fi

# Performance tips
echo ""
print_status "Performance Tips:"
echo "  • Use --registry-cache for CI/CD builds"
echo "  • Use --no-parallel only if you have memory constraints"
echo "  • Keep .dockerignore updated to reduce build context"
echo "  • Consider using multi-stage builds for smaller images"

# Cleanup old builders if too many
BUILDER_COUNT=$(docker buildx ls | wc -l)
if [ "$BUILDER_COUNT" -gt 5 ]; then
    print_warning "You have many Buildx builders. Consider cleaning up with: docker buildx prune"
fi

print_success "Fast build completed!"
