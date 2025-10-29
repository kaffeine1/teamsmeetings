# Contributing to Teams Meeting Plugin for Moodle

Thank you for considering contributing to this project! We welcome contributions from the community.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue on GitHub with:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected behavior** vs actual behavior
- **Moodle version** and PHP version
- **Plugin version**
- **Error messages** or logs (if available)

### Suggesting Enhancements

We welcome feature requests! Please create an issue with:

- Clear description of the feature
- Use cases and benefits
- Any relevant examples or mockups

### Pull Requests

We actively welcome your pull requests:

1. **Fork the repository** and create your branch from the main branch
2. **Make your changes** following our coding standards
3. **Test your changes** thoroughly
4. **Run PHPUnit tests** to ensure nothing breaks
5. **Write or update tests** for new features
6. **Update documentation** if needed
7. **Submit a pull request**

## Development Setup

### Prerequisites

- Moodle 4.1 or higher development environment
- PHP 7.4 or higher
- PHPUnit configured for Moodle
- Microsoft 365 account with Teams license for testing
- Azure AD app registration for API testing

### Running Tests

Initialize PHPUnit from your Moodle root:

```bash
php admin/tool/phpunit/cli/init.php
```

Run the plugin tests:

```bash
vendor/bin/phpunit mod/teamsmeetings
```

Run a specific test:

```bash
vendor/bin/phpunit mod/teamsmeetings/tests/calendar_fallback_test.php
```

## Coding Standards

### Moodle Coding Style

This plugin follows [Moodle coding standards](https://moodledev.io/general/development/policies/codingstyle):

- Use 4 spaces for indentation (no tabs)
- Opening braces on the same line
- Follow Moodle naming conventions
- Document all functions with PHPDoc comments
- Use Moodle's API functions where available

### Code Review Checklist

Before submitting, ensure:

- [ ] Code follows Moodle coding standards
- [ ] All functions have PHPDoc comments
- [ ] No hardcoded strings (use language strings)
- [ ] No debugging code left (print_r, var_dump, etc.)
- [ ] Error handling is appropriate
- [ ] Security best practices followed (SQL injection, XSS prevention)
- [ ] Tests pass
- [ ] No PHP warnings or notices

### Language Strings

When adding new features, add language strings in both:

- `lang/en/teamsmeetings.php` (English - required)
- `lang/it/teamsmeetings.php` (Italian - optional but appreciated)

Example:

```php
$string['mynewfeature'] = 'My new feature';
$string['mynewfeature_desc'] = 'Description of my new feature';
```

## Database Changes

If your contribution requires database changes:

1. Update `db/install.xml` with the new schema
2. Create an upgrade function in `db/upgrade.php`
3. Update `$plugin->version` in `version.php`
4. Test the upgrade path thoroughly

## API Changes

When modifying the Teams API integration:

1. Update `APIDOC.md` with any API changes
2. Consider backward compatibility
3. Test with both direct API and calendar fallback methods
4. Handle API errors gracefully

## Security Considerations

When contributing, please:

- Never commit API keys, secrets, or credentials
- Validate all user inputs
- Use Moodle's built-in security functions
- Follow OWASP guidelines for web security
- Respect user privacy and GDPR requirements

## Documentation

Update documentation when:

- Adding new features
- Changing configuration options
- Modifying API behavior
- Adding new permissions or capabilities

Files to update:

- `README.md` - User-facing documentation
- `APIDOC.md` - API documentation for developers
- Inline code comments
- Language strings

## Commit Messages

Write clear commit messages:

- Use present tense ("Add feature" not "Added feature")
- First line: brief summary (50 chars or less)
- Add detailed description if needed
- Reference issue numbers where applicable

Example:

```
Add support for recurring meetings

- Implement recurring meeting creation via Teams API
- Add UI controls for recurrence pattern selection
- Update database schema to store recurrence info
- Add tests for recurrence patterns

Fixes #123
```

## License

By contributing, you agree that your contributions will be licensed under the same GPL v3 license that covers this project.

## Questions?

If you have questions about contributing, feel free to:

- Open an issue with the "question" label
- Contact the maintainer: Michele Dipace (michele.dipace@kaffeine.net)

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive experience for everyone. We expect all contributors to:

- Be respectful and considerate
- Welcome newcomers and help them learn
- Accept constructive criticism gracefully
- Focus on what's best for the community

### Unacceptable Behavior

- Harassment, discrimination, or offensive comments
- Personal attacks or trolling
- Publishing others' private information
- Other conduct that could be considered inappropriate

Thank you for contributing! 🎉
