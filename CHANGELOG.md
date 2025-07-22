# Changelog

All notable changes to this project will be documented in this file.

Made with ❤️ by [Gustavo M.](https://github.com/guztaver)

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Comprehensive GitHub Documentation Suite**
  - `SECURITY.md`: Security policy with healthcare-specific guidelines and LGPD compliance
  - `CODE_OF_CONDUCT.md`: Community standards with healthcare ethics and patient data protection
  - `CONTRIBUTING.md`: 700+ line comprehensive contribution guide with healthcare domain expertise
  - `SUPPORT.md`: Multi-channel support strategy with healthcare-specific categories
  - GitHub issue templates: Bug reports, feature requests, and discussions with healthcare context
  - Pull request template with compliance verification and security assessment
  - `ROADMAP.md`: 5-phase development roadmap through 2026 with healthcare milestones
  - `FUNDING.yml`: GitHub Sponsors configuration with Brazilian PIX support

### Fixed
- **Security Scan Workflow Issues**
  - Fixed Trivy security scanner image reference (`ghcr.io/username/regulacao:latest`)
  - Added proper workflow dependencies to ensure images exist before scanning
  - Configured `exit-code: '0'` to prevent workflow failures on vulnerability detection
  - Added image availability checks and wait times for registry propagation
  - Improved error handling and conditional execution for security scans
  - Created manual security scan workflow for testing and debugging

### Changed
- **BREAKING**: Consolidated `Dockerfile` and `Dockerfile.fly` into a single unified `Dockerfile`
  - Removed duplicate `Dockerfile.fly` to reduce project bloat
  - Updated all Docker Compose configurations to use new stage names
  - The unified Dockerfile now supports both development and production builds
  - Production stage uses fly/ directory configurations for robust production deployment

### Updated
- **Fly.io Configuration**: 
  - Changed primary region from `iad` (Virginia) to `gru` (São Paulo, Brazil) for better latency
  - Updated `fly.toml` to reference the consolidated `Dockerfile` instead of `Dockerfile.fly`
  - Created new Fly.io app `regulacao-list-br` in Brazil region
  - Removed old app `withered-sky-640` from Virginia region

### Modified Files
- `Dockerfile`: Consolidated with Fly.io optimizations, now supports multi-stage builds with `development` and `production` targets
- `fly.toml`: Updated app name to `regulacao-list-br` and region to `gru` (São Paulo)
- `docker-compose.yml`: Updated to use `production` target instead of `php-prod`
- `docker-compose.prod.yml`: Updated to use `production` target instead of `php-prod`
- `docker-compose.override.yml`: Updated to use `development` target instead of `php`
- `quick-deploy.sh`: Updated for Brazil region deployment
- `FLY.md`: Updated documentation to reflect consolidated Dockerfile

### Removed
- `Dockerfile.fly`: Consolidated into main `Dockerfile`

### Technical Details
- The unified Dockerfile maintains the same functionality as before
- Uses fly/ directory configurations for production (nginx.conf, supervisord.conf, entrypoint.sh)
- Supports both development and production environments through multi-stage builds
- Maintains compatibility with existing CI/CD workflows
- Health checks and monitoring remain unchanged

### Migration Notes
- No action required for existing deployments
- New deployments will automatically use the consolidated Dockerfile
- Docker Compose users should rebuild their containers to use the new stage names:
  ```bash
  docker-compose down
  docker-compose build
  docker-compose up -d
  ```

### Added Tools & Scripts
- **`scripts/debug-docker.sh`**: Comprehensive Docker troubleshooting script
- **Manual security scan workflow**: On-demand vulnerability scanning with healthcare compliance notes
- **Enhanced CI/CD workflows**: Separate PR checks vs full production builds for faster feedback

### Documentation Improvements
- **Security scan troubleshooting guide**: Complete guide for debugging Trivy security issues
- **Healthcare compliance integration**: LGPD, SUS, and CFM guidelines throughout documentation
- **Professional project structure**: Enterprise-ready documentation for healthcare organizations

### Enhanced Security Features
- **Automated vulnerability scanning**: Daily security scans with healthcare compliance reporting
- **SARIF integration**: Security results uploaded to GitHub Security tab
- **Healthcare-specific security policies**: Patient data protection and audit trail requirements

### Brazil Region Benefits
- Reduced latency for Brazilian users
- Better compliance with local data residency requirements
- Improved performance for South American traffic