# Security Policy

## Reporting Security Vulnerabilities

We take security seriously. If you discover a security vulnerability in Lista da regulação, please report it to us as described below.

Made with ❤️ by [Gustavo M.](https://github.com/guztaver)

## Supported Versions

We provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## How to Report a Security Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via one of the following methods:

### Email
Send an email to: **contact@gustavoanjos.com** (if available) or the project maintainer directly.

### Private Security Advisory
Use GitHub's private vulnerability reporting feature:
1. Go to the Security tab of this repository
2. Click "Report a vulnerability"
3. Fill out the advisory form

## What to Include in Your Report

Please include as much of the following information as possible:

- **Type of vulnerability** (e.g., SQL injection, XSS, authentication bypass)
- **Step-by-step instructions** to reproduce the vulnerability
- **Proof of concept** or exploit code (if applicable)
- **Impact assessment** - what an attacker could achieve
- **Affected versions** and components
- **Any potential mitigations** you've identified

## Response Timeline

- **Initial Response**: Within 48 hours of receiving your report
- **Status Update**: Within 1 week with our assessment
- **Fix Timeline**: Critical vulnerabilities within 7 days, others within 30 days
- **Disclosure**: After fix is deployed and users have had time to update

## Security Best Practices

### For Developers

#### Authentication & Authorization
- Use Laravel's built-in authentication mechanisms
- Implement proper role-based access control (RBAC)
- Validate all user inputs and sanitize outputs
- Use CSRF protection on all forms

#### Data Protection
- Encrypt sensitive data at rest and in transit
- Use environment variables for secrets
- Implement proper session management
- Follow OWASP guidelines for secure coding

#### Database Security
- Use prepared statements to prevent SQL injection
- Implement proper database access controls
- Regular database security audits
- Backup encryption

### For Deployments

#### Infrastructure Security
```bash
# Use secure Docker practices
USER www-data
COPY --chown=www-data:www-data . /var/www/html

# Set secure file permissions
RUN chmod -R 755 storage bootstrap/cache
RUN chmod -R 644 .env
```

#### Environment Security
- Use HTTPS in production
- Set secure headers (HSTS, CSP, etc.)
- Regular security updates for dependencies
- Monitor for vulnerabilities in dependencies

### For Users

#### System Administration
- Keep the application and dependencies updated
- Use strong, unique passwords
- Enable two-factor authentication when available
- Regular security audits and penetration testing
- Monitor access logs for suspicious activity

#### Data Handling (Healthcare Compliance)
- Follow LGPD (Lei Geral de Proteção de Dados) requirements
- Implement proper patient data encryption
- Maintain audit trails for data access
- Regular data backup and recovery testing

## Known Security Considerations

### Healthcare Data (LGPD Compliance)
This application handles sensitive healthcare data and must comply with:

- **LGPD (Lei Geral de Proteção de Dados Pessoais)**
- **CFM (Conselho Federal de Medicina) guidelines**
- **SUS data protection requirements**

### Key Security Features Implemented

- ✅ Input validation and sanitization
- ✅ CSRF protection on all forms
- ✅ Secure file upload handling
- ✅ Database query protection (Eloquent ORM)
- ✅ Session security
- ✅ Rate limiting on API endpoints
- ✅ Secure headers configuration

### Areas Requiring Special Attention

- 🔒 **Patient data encryption** at rest
- 🔒 **Audit logging** for all data access
- 🔒 **Access control** based on healthcare roles
- 🔒 **Data retention policies** per LGPD requirements
- 🔒 **Secure file storage** for medical documents

## Security Updates

We will notify users of security updates through:

- GitHub Security Advisories
- Release notes with security tags
- Email notifications to registered administrators
- Documentation updates

## Third-Party Dependencies

We regularly monitor and update dependencies for security vulnerabilities using:

- **Dependabot** for automated dependency updates
- **npm audit** for Node.js dependencies
- **composer audit** for PHP dependencies
- **Trivy** security scanning in CI/CD

## Compliance & Certifications

### Current Compliance Status
- LGPD (Lei Geral de Proteção de Dados): 🔄 In Progress
- OWASP Top 10: ✅ Implemented
- Laravel Security Best Practices: ✅ Implemented

### Planned Certifications
- ISO 27001 compliance assessment
- Healthcare data security audit
- Penetration testing certification

## Security Contact

For non-critical security questions or suggestions:
- Open a GitHub discussion in the Security category
- Create an issue with the "security" label

For critical vulnerabilities:
- Use private reporting methods described above
- Contact maintainers directly for urgent issues

## Acknowledgments

We appreciate the security research community and will acknowledge contributors who responsibly disclose vulnerabilities:

- Hall of Fame for security researchers
- Public acknowledgment (with permission)
- Potential bug bounty rewards for critical findings

---

**Last Updated**: January 2025
**Next Review**: Every 6 months or after major releases

Thank you for helping keep Lista da regulação secure! 🔒

---

Made with ❤️ by [Gustavo M.](https://github.com/guztaver)
