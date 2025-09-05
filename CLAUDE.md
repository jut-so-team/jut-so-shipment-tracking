# jut-so Shipment Tracking Plugin Development Guidelines

## Code Standards

### WordPress Coding Standards
- Follow WordPress PHP Coding Standards
- Use proper indentation (tabs for PHP, spaces for alignment)
- Prefix all functions, classes, and global variables with `jutso_`
- Use meaningful, descriptive variable and function names
- Add inline documentation for complex logic

### Security Best Practices
- Always sanitize user input using WordPress sanitization functions
- Escape output using appropriate WordPress escaping functions
- Use nonces for all form submissions
- Validate capabilities before allowing actions
- Never trust data from external sources

### Performance Guidelines
- Minimize database queries
- Use WordPress transients for caching when appropriate
- Load assets only on pages where needed
- Use proper hooks with appropriate priority

### Plugin Architecture
- Keep code modular and organized
- Separate concerns (admin, frontend, API)
- Use OOP principles where appropriate
- Follow single responsibility principle

## Version Control Rules

### Commit Guidelines
- Make atomic commits (one feature/fix per commit)
- Write clear, descriptive commit messages
- Format: `type: description` (e.g., `feat: add tracking meta box`)
- Types: feat, fix, docs, style, refactor, test, chore

### Commit Frequency
- Commit after completing each major feature
- Commit before making significant changes
- Commit when code is working and tested

### Branch Strategy
- main/master: stable, production-ready code
- develop: integration branch for features
- feature/*: individual feature branches
- hotfix/*: urgent production fixes

## Testing Checklist
- [ ] Test meta box displays correctly in order edit page
- [ ] Test saving tracking codes
- [ ] Test API endpoint with various inputs
- [ ] Test email integration with test orders
- [ ] Test settings page saves correctly
- [ ] Test translations load properly
- [ ] Test with different user roles
- [ ] Test uninstall cleanup

## Development Commands
```bash
# Check PHP syntax
php -l filename.php

# WordPress coding standards check (if PHPCS installed)
phpcs --standard=WordPress filename.php

# Create POT file for translations
wp i18n make-pot . languages/jut-so-shipment-tracking.pot
```

## File Structure
```
jut-so-shipment-tracking/
├── jut-so-shipment-tracking.php    # Main plugin file
├── includes/
│   ├── class-jutso-admin.php       # Admin functionality
│   ├── class-jutso-api.php         # REST API endpoints
│   └── class-jutso-emails.php      # Email integration
├── assets/
│   ├── css/
│   │   └── admin.css               # Admin styles
│   └── js/
│       └── admin.js                # Admin scripts
├── languages/                       # Translation files
├── uninstall.php                    # Cleanup on uninstall
└── README.md                        # Plugin documentation
```

## Regular Tasks
1. Run syntax check before each commit
2. Test in WordPress admin after changes
3. Update version numbers consistently
4. Document any API changes
5. Keep translation files up to date