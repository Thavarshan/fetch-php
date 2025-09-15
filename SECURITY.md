# Security Policy

## Supported Versions

We actively support the following versions of Fetch PHP with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 3.x.x   | :white_check_mark: |
| 2.x.x   | :x:                |
| < 2.0   | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability in Fetch PHP, please follow responsible disclosure:

### How to Report

1. **Do not** create a public GitHub issue for security vulnerabilities
2. Email security concerns to: tjthavarshan@gmail.com
3. Include a detailed description of the vulnerability
4. Provide steps to reproduce the issue
5. Include any proof-of-concept code (if applicable)

### What to Expect

- **Acknowledgment**: We will acknowledge receipt within 48 hours
- **Investigation**: Initial assessment within 5 business days
- **Updates**: Regular updates on progress every 5-7 days
- **Resolution**: We aim to resolve critical issues within 30 days

### Disclosure Timeline

- **Day 0**: Vulnerability reported
- **Day 1-2**: Acknowledgment sent
- **Day 1-5**: Initial assessment and severity classification
- **Day 5-30**: Development and testing of fix
- **Day 30+**: Public disclosure after fix is released

### Security Best Practices

When using Fetch PHP:

1. **Always validate input** before making HTTP requests
2. **Use HTTPS** for all external API calls
3. **Sanitize response data** before processing
4. **Implement proper authentication** using bearer tokens or API keys
5. **Set appropriate timeouts** to prevent hanging requests
6. **Use the latest version** to benefit from security patches

### Scope

This security policy covers:

- The core Fetch PHP library (`src/Fetch/`)
- Helper functions and utilities
- Authentication mechanisms
- HTTP request/response handling

Out of scope:

- Third-party dependencies (report to respective maintainers)
- Example code in documentation
- Development tools and scripts

## Hall of Fame

We recognize security researchers who help improve Fetch PHP:

<!-- Contributors will be listed here after responsible disclosure -->

Thank you for helping keep Fetch PHP secure!