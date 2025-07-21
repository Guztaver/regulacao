# Pull Request

## Description

<!-- Provide a brief description of the changes in this PR -->

## Type of Change

<!-- Mark the type of change with an "x" -->

- [ ] 🐛 Bug fix (non-breaking change which fixes an issue)
- [ ] ✨ New feature (non-breaking change which adds functionality)
- [ ] 💥 Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] 📚 Documentation update
- [ ] 🎨 Code style/formatting changes
- [ ] ♻️ Code refactoring (no functional changes)
- [ ] ⚡ Performance improvement
- [ ] 🔒 Security enhancement
- [ ] 🧪 Test addition or improvement
- [ ] 🚀 CI/CD improvement
- [ ] 🧹 Chore/maintenance

## Related Issues

<!-- Link to related issues using keywords like "Fixes #123" or "Addresses #456" -->

- Fixes #
- Addresses #
- Related to #

## Changes Made

<!-- Describe the specific changes made in this PR -->

### Backend Changes
- [ ] Models updated
- [ ] Controllers modified
- [ ] Routes added/changed
- [ ] Database migrations
- [ ] API endpoints modified
- [ ] Validation rules updated
- [ ] Authorization policies changed

### Frontend Changes
- [ ] Vue components updated
- [ ] New UI components added
- [ ] Styling changes
- [ ] Form validation updated
- [ ] API integration modified
- [ ] Responsive design improvements

### Infrastructure Changes
- [ ] Docker configuration updated
- [ ] CI/CD pipeline modified
- [ ] Environment variables added
- [ ] Deployment scripts updated
- [ ] Performance optimizations

## Healthcare Compliance

<!-- For healthcare-related changes -->

- [ ] ✅ **LGPD Compliance**: Changes respect Brazilian data protection laws
- [ ] ✅ **SUS Integration**: SUS number validation and formatting maintained
- [ ] ✅ **Data Security**: Sensitive healthcare data properly protected
- [ ] ✅ **Audit Trail**: User actions properly logged for compliance
- [ ] ✅ **Patient Privacy**: No patient data exposed in logs or errors
- [ ] ⚠️ **N/A**: This PR doesn't involve healthcare data

## Testing

<!-- Describe the testing performed -->

### Test Coverage
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Frontend component tests added/updated
- [ ] Manual testing completed
- [ ] Browser testing (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsive testing
- [ ] Accessibility testing

### Test Results
```bash
# Backend tests
./vendor/bin/pest --coverage
# Result: ___% coverage

# Frontend tests  
npm run test
# Result: All tests pass

# Build test
./scripts/fast-build.sh
# Result: Build successful in ___m ___s
```

## Security Checklist

- [ ] 🔐 **Input Validation**: All user inputs properly validated
- [ ] 🛡️ **SQL Injection**: No raw SQL queries with user input
- [ ] 🚫 **XSS Prevention**: Output properly sanitized
- [ ] 🔑 **Authentication**: Proper authentication checks implemented
- [ ] 👮 **Authorization**: Appropriate permission checks added
- [ ] 📝 **Audit Logging**: Security-relevant actions logged
- [ ] 🔒 **Data Encryption**: Sensitive data encrypted at rest/transit
- [ ] ⚠️ **N/A**: This PR doesn't involve security-sensitive changes

## Performance Impact

<!-- Describe performance considerations -->

- [ ] ⚡ **Performance Improvement**: Changes improve application performance
- [ ] ➡️ **No Performance Impact**: Changes don't affect performance
- [ ] ⚠️ **Potential Performance Impact**: Changes may affect performance (explain below)

### Performance Notes
<!-- If there's potential performance impact, explain: -->
- Database query optimization: 
- Frontend bundle size impact:
- Memory usage changes:
- Build time impact:

## Breaking Changes

<!-- If this is a breaking change, describe what breaks and how to migrate -->

- [ ] ❌ **No Breaking Changes**
- [ ] ⚠️ **Breaking Changes** (describe below)

### Migration Guide
<!-- If breaking changes exist, provide migration instructions -->

```bash
# Steps to migrate existing installations:
# 1. 
# 2. 
# 3. 
```

## Screenshots/Videos

<!-- Add screenshots for UI changes or videos for complex interactions -->

### Before
<!-- Screenshot or description of current behavior -->

### After
<!-- Screenshot or description of new behavior -->

## Documentation

- [ ] 📖 **README updated** (if needed)
- [ ] 📋 **API documentation updated** (if API changes)
- [ ] 📝 **Code comments added** for complex logic
- [ ] 🏥 **Healthcare compliance docs updated** (if applicable)
- [ ] 🐳 **Docker docs updated** (if infrastructure changes)
- [ ] ⚠️ **N/A**: No documentation updates needed

## Deployment Notes

<!-- Any special considerations for deployment -->

- [ ] 🗄️ **Database migrations required**
- [ ] 🔧 **Environment variables need updating**
- [ ] 📦 **Dependencies updated** (composer/npm)
- [ ] 🔄 **Cache clearing required**
- [ ] 🚀 **Special deployment steps** (describe below)
- [ ] ✅ **Standard deployment** (no special steps)

### Special Deployment Steps
<!-- If special steps are needed: -->

```bash
# Before deployment:
# 1. 

# After deployment:
# 1. 
```

## Checklist

<!-- Ensure all items are checked before requesting review -->

### Code Quality
- [ ] 🎯 **Code follows project conventions**
- [ ] 🧹 **Code is clean and readable**
- [ ] 📝 **Complex logic is commented**
- [ ] 🔄 **No duplicate code**
- [ ] ⚡ **Performance considered**
- [ ] 🛡️ **Error handling implemented**

### Testing & Quality Assurance
- [ ] ✅ **All tests pass locally**
- [ ] 📊 **Test coverage maintained/improved**
- [ ] 🔍 **Code linted and formatted**
- [ ] 🌐 **Cross-browser testing completed**
- [ ] 📱 **Mobile responsiveness checked**
- [ ] ♿ **Accessibility guidelines followed**

### Healthcare & Compliance
- [ ] 🏥 **Healthcare data handled securely**
- [ ] 📋 **LGPD compliance maintained**
- [ ] 🆔 **SUS number validation working**
- [ ] 📊 **Audit trails implemented**
- [ ] 🔒 **Patient privacy protected**

### Documentation & Communication
- [ ] 📖 **PR description is clear and complete**
- [ ] 🔗 **Related issues linked**
- [ ] 📸 **Screenshots provided for UI changes**
- [ ] 📚 **Documentation updated**
- [ ] 💬 **Ready for code review**

## Additional Notes

<!-- Any additional information that reviewers should know -->

## Reviewer Notes

<!-- For reviewers - what should they focus on? -->

**Please pay special attention to:**
- [ ] Security implications
- [ ] Healthcare compliance
- [ ] Performance impact
- [ ] Database changes
- [ ] API compatibility
- [ ] User experience
- [ ] Error handling

---

## Post-Merge Tasks

<!-- Tasks to complete after the PR is merged -->

- [ ] 📋 **Update project board**
- [ ] 📝 **Update changelog**
- [ ] 🚀 **Deploy to staging**
- [ ] ✅ **Verify deployment**
- [ ] 📊 **Monitor metrics**
- [ ] 🗑️ **Delete feature branch**

---

**Reviewer Assignment**: @<!-- mention specific reviewers if needed -->

**Estimated Review Time**: <!-- How long should this take to review? --> 

Thank you for contributing to Regulação List! 🏥