# AGENTS.md - Development Guide for iSeller Store

This guide provides essential information for agentic coding agents working on the iSeller Store e-commerce platform.

## Build/Test Commands

### Available Commands
```bash
# JavaScript Testing (Jest - for Notiflix library only)
cd assets/dist/notiflix-Notiflix-67ba12d
npm test                                    # Run all tests
npm run test:watch                         # Watch mode
npm run test:coverage                       # With coverage

# Code Quality (Notiflix library only)
npm run lint                                # ESLint
npm run lint:fix                            # Auto-fix ESLint issues
npm run stylelint                           # CSS linting

# Build (Notiflix library only)
npm run build                               # Production build
npm run dev                                 # Development build
```

### Manual Testing for Main Application
```bash
# No automated test suite - manual testing required:
# 1. Test user authentication flow (login.php, registro.php, logout.php)
# 2. Test product catalog loading (api/productos.php)
# 3. Test shopping cart functionality (add/update/remove items)
# 4. Test checkout process (checkout.php)
# 5. Test rewards system (api/recompensas.php)
```

### Running Single Tests
```bash
# Notiflix library specific tests
cd assets/dist/notiflix-Notiflix-67ba12d
npx jest test/e2e/notify/notify.test.ts     # Single test file
npx jest --testNamePattern="specific test"  # By test name
```

## Code Style Guidelines

### PHP Code Standards
- **File Encoding**: UTF-8 without BOM
- **Indentation**: 4 spaces (no tabs)
- **Line Endings**: LF (Unix style)
- **PHP Tags**: Use `<?php` only, never `?>` at end of files
- **Naming Conventions**:
  - Variables: camelCase (`$usuarioActual`, `$totalProductos`)
  - Functions: camelCase with descriptive names (`isLoggedIn()`, `calcularPrecios()`)
  - Classes: PascalCase (`CalculadoraPrecios`, `Cart`)
  - Constants: UPPER_SNAKE_CASE (`DB_USER`, `MAX_PRODUCTS`)
  - Database tables: snake_case (`usuarios`, `productos`, `recompensas_usuario`)

### PHP Documentation
```php
/**
 * Brief description of the function
 * @param type $param Description of parameter
 * @return type Description of return value
 */
function functionName($param) {
    // Implementation
}
```

### Security Practices
- **Always use prepared statements** for database queries
- **Password hashing**: Use `password_hash()` and `password_verify()`
- **Input validation**: Sanitize all user inputs with `trim()`, `htmlspecialchars()`
- **Session security**: Use `session_regenerate_id(true)` on login
- **SQL Injection Prevention**: Never concatenate SQL strings

### Database Query Patterns
```php
// Correct - Prepared Statements
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Always close statements
$stmt->close();
```

### JavaScript/TypeScript Standards
- **Indentation**: 2 spaces
- **Quotes**: Single quotes for strings
- **Variables**: camelCase
- **Functions**: camelCase with descriptive names
- **jQuery**: Use jQuery-style event handlers and DOM manipulation
- **Modern JS**: Use `const`/`let`, arrow functions where appropriate

### JavaScript Patterns
```javascript
// Event delegation (jQuery style)
$(document).on('click', '.btn-add-to-car', function() {
    const id = $(this).data('add-id');
    // Handle event
});

// Async/await for API calls
async function cargarProductos() {
    try {
        const response = await fetch('api/productos.php');
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error loading products:', error);
    }
}
```

### CSS Guidelines
- **CSS Custom Properties**: Use CSS variables defined in `:root`
- **Naming**: BEM methodology for component styles
- **Responsive Design**: Mobile-first approach with Bootstrap 5
- **Colors**: Use defined CSS custom properties (`--primary-color`, `--text-primary`)
- **Spacing**: Follow Bootstrap's spacing utilities

### CSS Structure
```css
/* CSS Custom Properties */
:root {
    --primary-color: #6FAF7A;
    --text-primary: #1F2933;
}

/* Component styles */
.product-card {
    background: var(--bg-color);
    border-radius: var(--radius-md);
}

.product-card__title {
    font-weight: 600;
    color: var(--text-primary);
}
```

## File Organization

### Core Structure
```
/
├── core/                   # Core utilities and classes
│   ├── db.php             # Database connections
│   ├── session.php        # Session management
│   ├── la-carta.php       # Shopping cart class
│   └── *.php              # Core utilities
├── api/                   # API endpoints
│   ├── productos.php      # Product catalog API
│   ├── perfil_data.php    # User profile API
│   └── recompensas.php    # Rewards system API
├── assets/                # Frontend assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── img/               # Images
├── *.php                  # Main pages (index.php, login.php, etc.)
└── sql/                   # Database schemas
```

### Import/Include Patterns
```php
// Core dependencies first
require_once('core/db.php');
require_once('core/session.php');
require_once('core/_tasas_cambio.php');

// Then specific utilities
require_once('core/_calculadrora_precios.php');
```

## Error Handling

### PHP Error Handling
- **Database errors**: Use proper error checking and user-friendly messages
- **Form validation**: Server-side validation with clear error messages
- **File operations**: Check file existence before operations
- **API responses**: Use consistent JSON response format

```php
// API Response Pattern
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'Error description',
    'data' => null
]);
```

### JavaScript Error Handling
- **API calls**: Use try-catch with user feedback
- **DOM operations**: Check element existence before manipulation
- **User feedback**: Use Notiflix for consistent notifications

## Development Workflow

### Before Making Changes
1. **Understand the existing codebase patterns** - this is a traditional PHP application
2. **Test manually** - no automated test suite for the main application
3. **Check database connections** - two connections exist: `$conexion` and `$conexion_store`
4. **Verify session management** - user authentication is handled via core/session.php

### Common Patterns
- **API endpoints** use JSON responses with `success`, `message`, and `data` fields
- **Database queries** use prepared statements exclusively
- **Frontend interactions** use jQuery with event delegation
- **Shopping cart** uses IndexedDB for client-side persistence
- **User sessions** managed through PHP session functions in core/session.php

### Important Notes
- **No Composer**: Dependencies are managed manually
- **No build process**: JavaScript and CSS are served directly
- **jQuery-based**: Not a modern SPA - traditional multi-page application
- **Bootstrap 5**: UI framework for responsive design
- **Manual testing**: No automated testing for main application code

## Testing Checklist

When making changes, manually verify:
- [ ] User login/logout functionality
- [ ] Product catalog loads correctly
- [ ] Shopping cart operations (add/update/remove)
- [ ] Checkout process works
- [ ] Mobile responsiveness
- [ ] Database queries execute without errors
- [ ] Session data persists correctly
- [ ] API endpoints return proper JSON responses

## Security Reminders

- Always sanitize user inputs
- Use prepared statements for all database queries
- Verify user authentication before protected operations
- Use proper session management
- Validate file uploads and file operations
- Implement proper error handling without exposing system details