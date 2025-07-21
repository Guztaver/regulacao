# Security Scan Troubleshooting Guide

This guide helps troubleshoot common issues with the Trivy security scanning workflows in the Regulação List project.

## Common Issues and Solutions

### 1. Image Not Found Errors

**Error:**
```
failed to parse the image name: could not parse reference: ghcr.io/username/regulacao:latest
```

**Causes:**
- Image hasn't been built and pushed to registry yet
- Wrong image name or tag
- Authentication issues with GitHub Container Registry

**Solutions:**

#### Check if Image Exists
```bash
# Run the debug script to check image status
./scripts/debug-docker.sh --remote --image username/regulacao

# Or manually check with docker
docker manifest inspect ghcr.io/username/regulacao:latest
```

#### Build and Push Image First
```bash
# Build and push using fast build script
./scripts/fast-build.sh --push --registry-cache

# Or manual build and push
docker build -t ghcr.io/username/regulacao:latest .
docker push ghcr.io/username/regulacao:latest
```

#### Fix Repository Name
Update the workflow if the repository name changed:

```yaml
env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}  # Should be "owner/repo"
```

### 2. Authentication Issues

**Error:**
```
unauthorized: authentication required
```

**Solutions:**

#### Check GitHub Token Permissions
Ensure the workflow has proper permissions:

```yaml
permissions:
  contents: read
  packages: write  # Required for GHCR access
  security-events: write  # Required for security tab
```

#### Verify Registry Login
The workflow should include:

```yaml
- name: Log in to Container Registry
  uses: docker/login-action@v3
  with:
    registry: ${{ env.REGISTRY }}
    username: ${{ github.actor }}
    password: ${{ secrets.GITHUB_TOKEN }}
```

### 3. Workflow Timing Issues

**Error:**
```
Error: Process completed with exit code 1
```

**Cause:** Security scan runs before image is fully available in registry

**Solution:** Add delay or dependency checks:

```yaml
- name: Wait for image to be available
  run: |
    echo "Waiting for image to be available in registry..."
    sleep 30

- name: Check if image exists
  run: |
    for i in {1..5}; do
      if docker manifest inspect ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:latest; then
        echo "Image found!"
        break
      fi
      echo "Attempt $i: Image not found, waiting..."
      sleep 10
    done
```

### 4. Security Scan Configuration Issues

#### Exit Code Problems
**Issue:** Workflow fails when vulnerabilities are found

**Solution:** Set exit-code to 0 to prevent workflow failure:

```yaml
- name: Run Trivy vulnerability scanner
  uses: aquasecurity/trivy-action@master
  with:
    image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:latest
    format: 'sarif'
    output: 'trivy-results.sarif'
    exit-code: '0'  # Don't fail workflow on vulnerabilities
    severity: 'CRITICAL,HIGH,MEDIUM'
```

#### SARIF Upload Issues
**Issue:** SARIF file not uploading to Security tab

**Solution:** Ensure proper permissions and file existence:

```yaml
- name: Upload Trivy scan results
  uses: github/codeql-action/upload-sarif@v3
  if: always()  # Upload even if scan finds issues
  with:
    sarif_file: 'trivy-results.sarif'
```

### 5. Manual Security Scanning

#### Run Security Scan Manually

Use the dedicated security scan workflow:

1. Go to **Actions** tab in GitHub
2. Select **Security Scan** workflow
3. Click **Run workflow**
4. Choose options:
   - Image tag (default: latest)
   - Severity levels
   - Scan type

#### Local Security Scanning

```bash
# Install Trivy locally
curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh -s -- -b /usr/local/bin

# Scan local image
trivy image regulacao-list:latest

# Scan with specific severity
trivy image --severity CRITICAL,HIGH regulacao-list:latest

# Generate SARIF output
trivy image --format sarif --output results.sarif regulacao-list:latest
```

### 6. Healthcare-Specific Security Considerations

#### LGPD Compliance
- **Critical vulnerabilities** must be addressed immediately
- **High vulnerabilities** should be fixed within 30 days
- **Medium vulnerabilities** should be reviewed and scheduled

#### Patient Data Security
- Monitor for vulnerabilities affecting:
  - Database security
  - File upload mechanisms
  - Authentication systems
  - Session management

#### Audit Requirements
- Keep security scan results for compliance audits
- Document remediation efforts
- Track vulnerability resolution times

## Debugging Steps

### Step 1: Check Build Status
```bash
# Check if the build workflow completed successfully
gh run list --workflow="Docker Build and Deploy"

# View specific run details
gh run view <run-id>
```

### Step 2: Verify Image Availability
```bash
# List available tags
docker run --rm quay.io/skopeo/skopeo list-tags docker://ghcr.io/username/regulacao

# Check image manifest
docker manifest inspect ghcr.io/username/regulacao:latest
```

### Step 3: Test Security Scan Locally
```bash
# Use debug script
./scripts/debug-docker.sh --remote --image username/regulacao

# Run trivy directly
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
  aquasec/trivy image ghcr.io/username/regulacao:latest
```

### Step 4: Check Workflow Logs
1. Go to **Actions** tab
2. Click on failed workflow run
3. Expand **security-scan** job
4. Check each step for error details

## Prevention Strategies

### 1. Conditional Security Scans
Only run security scans when images are actually built:

```yaml
security-scan:
  needs: build
  if: needs.build.result == 'success'
```

### 2. Image Tag Strategy
Use consistent tagging strategy:

```yaml
tags: |
  type=ref,event=branch
  type=ref,event=pr
  type=raw,value=latest,enable={{is_default_branch}}
```

### 3. Error Handling
Implement robust error handling:

```yaml
- name: Run security scan with retries
  run: |
    for i in {1..3}; do
      if trivy image ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:latest; then
        echo "Security scan successful"
        break
      fi
      echo "Attempt $i failed, retrying..."
      sleep 10
    done
```

### 4. Regular Maintenance
- **Daily**: Automated security scans
- **Weekly**: Review and triage vulnerabilities
- **Monthly**: Update base images and dependencies
- **Quarterly**: Security policy review

## Getting Help

### Internal Resources
- Check `SECURITY.md` for security policy
- Review `DOCKER_OPTIMIZATION.md` for build optimization
- Use `./scripts/debug-docker.sh` for troubleshooting

### GitHub Issues
Create an issue with:
- **Label**: `security`, `bug`
- **Template**: Bug report template
- **Include**: 
  - Workflow run URL
  - Error messages
  - Steps attempted

### Security Vulnerabilities
For security issues in dependencies:
- Use GitHub Security Advisories
- Follow responsible disclosure
- Document remediation in security tab

## Example Workflow Fixes

### Fixed Main Workflow
```yaml
security-scan:
  needs: build
  runs-on: ubuntu-latest
  if: github.event_name == 'push' && github.ref == 'refs/heads/main' && needs.build.result == 'success'
  permissions:
    contents: read
    security-events: write

  steps:
    - name: Wait for image availability
      run: sleep 30

    - name: Run Trivy vulnerability scanner
      uses: aquasecurity/trivy-action@master
      with:
        image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:latest
        format: 'sarif'
        output: 'trivy-results.sarif'
        timeout: '10m'
        exit-code: '0'
        severity: 'CRITICAL,HIGH,MEDIUM'

    - name: Upload scan results
      uses: github/codeql-action/upload-sarif@v3
      if: always()
      with:
        sarif_file: 'trivy-results.sarif'
```

### Fixed PR Workflow
```yaml
security-check:
  needs: [changes, build-test]
  if: always() && needs.changes.outputs.docker == 'true' && needs.build-test.result == 'success'
  
  steps:
    - name: Run Trivy vulnerability scanner
      uses: aquasecurity/trivy-action@master
      with:
        image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:pr-${{ github.event.number }}
        format: 'sarif'
        output: 'trivy-results.sarif'
        timeout: '5m'
        exit-code: '0'
```

## Best Practices Summary

1. **Always check if image exists** before scanning
2. **Use exit-code: '0'** to prevent workflow failures
3. **Add proper dependencies** between jobs
4. **Include wait times** for registry propagation
5. **Set appropriate timeouts** for large images
6. **Filter severity levels** based on requirements
7. **Upload results even on failures** using `if: always()`
8. **Document security decisions** for compliance

---

**Last Updated**: January 2025

For the most current troubleshooting information, check the GitHub repository and recent issues.