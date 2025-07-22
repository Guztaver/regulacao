# Contributing to Lista da regulação

Thank you for your interest in contributing to Lista da regulação! This document provides guidelines and information for contributors to help maintain code quality and ensure a smooth collaboration process.

Made with ❤️ by Gustavo M. (https://github.com/guztaver)

## Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Process](#contributing-process)
- [Code Standards](#code-standards)
- [Testing Guidelines](#testing-guidelines)
- [Documentation](#documentation)
- [Healthcare Compliance](#healthcare-compliance)
- [Performance Guidelines](#performance-guidelines)
- [Security Considerations](#security-considerations)
- [Community](#community)

## Getting Started

### Prerequisites

Before contributing, make sure you have:

- **PHP 8.4+** with required extensions
- **Node.js 20+** and npm
- **Docker** and Docker Compose
- **Git** with proper configuration
- Basic understanding of **Laravel** and **Vue.js**
- Familiarity with **healthcare data handling** principles

### First Contribution

1. **Read our documentation**:
   - [Code of Conduct](CODE_OF_CONDUCT.md)
   - [Security Policy](SECURITY.md)
   - [Docker Optimization Guide](DOCKER_OPTIMIZATION.md)

2. **Look for good first issues**:
   - Check issues labeled `good first issue`
   - Browse `help wanted` labeled issues
   - Review open feature requests

3. **Join the community**:
   - Introduce yourself in discussions
   - Ask questions if anything is unclear
   - Follow project announcements

## Development Setup

### Quick Start (Recommended)

```bash
# Clone the repository
git clone https://github.com/your-username/regulacao-list.git
cd regulacao-list

# Fast Docker setup (5-8 minutes)
./scripts/fast-build.sh --target development
docker-compose up -d

# Or traditional setup
make setup
```

### Manual Setup

```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start development server
php artisan serve
```

### Development Tools

```bash
# Run tests
make test
./vendor/bin/pest

# Code formatting
make lint
make format

# Docker development
make dev           # Start development environment
make build-fast    # Fast optimized build
make logs          # View logs
```

## Contributing Process

### 1. Issue First Approach

- **Always create or comment on an issue** before starting work
- **Discuss your approach** with maintainers
- **Get approval** for significant changes
- **Link issues** to pull requests

### 2. Fork and Branch

```bash
# Fork the repository on GitHub
# Clone your fork
git clone https://github.com/YOUR-USERNAME/regulacao-list.git

# Create a feature branch
git checkout -b feature/your-feature-name
# or
git checkout -b bugfix/issue-number-description
# or
git checkout -b docs/documentation-update
```

### 3. Branch Naming Convention

- **Features**: `feature/short-description`
- **Bug fixes**: `bugfix/issue-123-short-description`
- **Documentation**: `docs/what-you-are-documenting`
- **Performance**: `perf/optimization-description`
- **Security**: `security/vulnerability-description`
- **Chores**: `chore/maintenance-task`

### 4. Commit Guidelines

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
# Format: type(scope): description
# Examples:
git commit -m "feat(patients): add SUS number validation"
git commit -m "fix(entries): resolve filtering bug with date ranges"
git commit -m "docs(api): update endpoint documentation"
git commit -m "perf(build): optimize Docker build time"
git commit -m "test(patients): add comprehensive patient creation tests"
git commit -m "security(auth): implement rate limiting"
```

**Commit Types**:
- `feat`: New features
- `fix`: Bug fixes
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `perf`: Performance improvements
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `security`: Security improvements

### 5. Pull Request Process

1. **Update your branch**:
   ```bash
   git checkout main
   git pull upstream main
   git checkout your-branch
   git rebase main
   ```

2. **Run quality checks**:
   ```bash
   # Run tests
   make test
   
   # Check code style
   make lint
   
   # Build and test Docker image
   make build-fast
   ```

3. **Create pull request**:
   - Use descriptive title following conventional commits
   - Fill out the PR template completely
   - Link related issues
   - Add screenshots for UI changes
   - Request appropriate reviewers

4. **PR Template Checklist**:
   - [ ] Tests added/updated
   - [ ] Documentation updated
   - [ ] Code follows style guidelines
   - [ ] Security considerations reviewed
   - [ ] Healthcare compliance maintained
   - [ ] Performance impact assessed
   - [ ] Breaking changes documented

## Code Standards

### PHP (Laravel)

#### Style Guide
- Follow **PSR-12** coding standards
- Use **Laravel best practices**
- Follow **SOLID principles**

```php
<?php

// ✅ Good: Descriptive names, proper typing
class PatientController extends Controller
{
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => new PatientResource($patient),
        ], 201);
    }
}

// ❌ Bad: Poor naming, no types
class PatientController extends Controller
{
    public function store($req)
    {
        $p = Patient::create($req->all());
        return $p;
    }
}
```

#### Database
- Use **migrations** for all schema changes
- Add **proper indexes** for performance
- Use **Eloquent relationships** properly
- **Never** use raw queries for user input

```php
// ✅ Good: Proper migration
Schema::create('patients', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255)->index();
    $table->string('sus_number', 15)->unique()->nullable();
    $table->string('email')->unique()->nullable();
    $table->string('phone', 20)->nullable();
    $table->timestamps();
    
    $table->index(['created_at', 'name']);
});
```

#### Security
- Always use **Form Requests** for validation
- Implement **authorization policies**
- Use **mass assignment protection**
- **Sanitize** all outputs

```php
// ✅ Good: Proper validation and authorization
class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Patient::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sus_number' => ['nullable', 'string', 'size:15', 'unique:patients'],
            'email' => ['nullable', 'email', 'unique:patients'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
```

### JavaScript/Vue.js

#### Style Guide
- Follow **Vue 3 Composition API** patterns
- Use **TypeScript** when possible
- Follow **ESLint** configuration

```vue
<!-- ✅ Good: Composition API, proper typing -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Patient } from '@/types'

interface Props {
  patient: Patient
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false
})

const isEditing = ref(false)
const displayName = computed(() => 
  props.patient.name || 'Nome não informado'
)
</script>

<template>
  <div class="patient-card">
    <h3>{{ displayName }}</h3>
    <button 
      v-if="!readonly" 
      @click="isEditing = !isEditing"
      class="btn btn-primary"
    >
      Editar
    </button>
  </div>
</template>
```

#### Component Structure
- Use **Single File Components**
- Implement **proper prop validation**
- Use **composables** for reusable logic
- Follow **accessibility guidelines**

### CSS/Styling

- Use **Tailwind CSS** utilities
- Follow **mobile-first** approach
- Maintain **design system** consistency
- Use **semantic class names** when needed

```vue
<!-- ✅ Good: Tailwind utilities, responsive design -->
<template>
  <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
    <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-4">
      Patient Information
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Content -->
    </div>
  </div>
</template>
```

## Testing Guidelines

### PHP Tests (Pest)

```php
// ✅ Good: Descriptive test names, proper setup
test('authenticated user can create patient with valid data')
    ->actingAs(User::factory()->create())
    ->post('/api/patients', [
        'name' => 'João Silva',
        'sus_number' => '123456789012345',
        'email' => 'joao@example.com',
    ])
    ->assertStatus(201)
    ->assertJsonStructure([
        'success',
        'data' => [
            'id',
            'name',
            'sus_number',
            'email',
            'created_at',
        ],
    ]);

test('patient creation requires authentication')
    ->post('/api/patients', [
        'name' => 'João Silva',
    ])
    ->assertStatus(401);
```

### Frontend Tests

```javascript
// ✅ Good: Component testing with realistic scenarios
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PatientForm from '@/components/PatientForm.vue'

describe('PatientForm', () => {
  it('validates SUS number format correctly', async () => {
    const wrapper = mount(PatientForm)
    
    await wrapper.find('input[name="sus_number"]').setValue('invalid')
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.find('.error-message').text())
      .toContain('SUS number must be 15 digits')
  })
  
  it('submits form with valid data', async () => {
    const wrapper = mount(PatientForm)
    
    await wrapper.find('input[name="name"]').setValue('João Silva')
    await wrapper.find('input[name="sus_number"]').setValue('123456789012345')
    
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.emitted('submit')).toBeTruthy()
  })
})
```

### Testing Requirements

- **Minimum 80% code coverage** for new features
- **Integration tests** for API endpoints
- **Component tests** for UI components
- **Browser tests** for critical user flows
- **Security tests** for authentication and authorization

```bash
# Run specific test suites
./vendor/bin/pest --coverage                    # Backend with coverage
npm run test                                    # Frontend tests
./vendor/bin/pest --group=integration          # Integration tests only
./vendor/bin/pest --group=security             # Security tests only
```

## Documentation

### Code Documentation

```php
/**
 * Create a new patient with SUS number validation.
 * 
 * This method handles patient creation with proper validation
 * of Brazilian SUS (Sistema Único de Saúde) numbers and ensures
 * LGPD compliance for personal data handling.
 *
 * @param StorePatientRequest $request Validated request data
 * @return JsonResponse Patient creation response
 * 
 * @throws ValidationException When SUS number format is invalid
 * @throws AuthorizationException When user lacks create permission
 */
public function store(StorePatientRequest $request): JsonResponse
{
    // Implementation
}
```

### API Documentation

- Use **OpenAPI/Swagger** specifications
- Include **request/response examples**
- Document **error responses**
- Add **authentication requirements**

### Updating Documentation

When making changes, update relevant documentation:

- **README.md** for setup or usage changes
- **API documentation** for endpoint changes
- **CHANGELOG.md** for all changes
- **Component documentation** for UI changes

## Healthcare Compliance

### LGPD (Lei Geral de Proteção de Dados)

- **Data minimization**: Only collect necessary data
- **Purpose limitation**: Use data only for stated purposes
- **Consent management**: Track and respect user consent
- **Right to deletion**: Implement data removal capabilities
- **Data portability**: Allow data export in standard formats

### SUS Integration

- **Validate SUS numbers** using official algorithms
- **Respect SUS data standards** and formatting
- **Implement proper audit trails** for SUS data access
- **Follow CFM guidelines** for medical data handling

### Code Examples

```php
// ✅ Good: LGPD compliant data handling
class PatientService
{
    public function createPatient(array $data): Patient
    {
        // Log data processing for audit trail
        Log::info('Patient creation initiated', [
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        
        // Only store consented data
        $allowedFields = $this->getConsentedFields($data['consent']);
        $filteredData = Arr::only($data, $allowedFields);
        
        return Patient::create($filteredData);
    }
    
    public function deletePatient(Patient $patient): bool
    {
        // LGPD right to deletion with audit trail
        Log::warning('Patient deletion requested', [
            'patient_id' => $patient->id,
            'requested_by' => auth()->id(),
            'timestamp' => now(),
        ]);
        
        // Anonymize instead of hard delete for audit purposes
        return $this->anonymizePatient($patient);
    }
}
```

## Performance Guidelines

### Database Performance

```php
// ✅ Good: Efficient queries with proper relationships
class PatientController extends Controller
{
    public function index(Request $request)
    {
        return Patient::query()
            ->with(['entries:id,patient_id,created_at'])
            ->when($request->search, fn($query, $search) => 
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('sus_number', 'like', "%{$search}%")
            )
            ->select(['id', 'name', 'sus_number', 'email', 'created_at'])
            ->latest()
            ->paginate(15);
    }
}

// ❌ Bad: N+1 queries
public function index()
{
    $patients = Patient::all(); // Loads all patients
    
    foreach ($patients as $patient) {
        $patient->entries; // N+1 query problem
    }
    
    return $patients;
}
```

### Frontend Performance

```vue
<!-- ✅ Good: Optimized component with lazy loading -->
<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'

// Lazy load heavy components
const PatientModal = defineAsyncComponent(
  () => import('@/components/PatientModal.vue')
)

const patients = ref<Patient[]>([])
const searchTerm = ref('')

// Computed for efficient filtering
const filteredPatients = computed(() => 
  patients.value.filter(patient => 
    patient.name.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
)
</script>

<template>
  <div>
    <!-- Efficient v-for with key -->
    <div 
      v-for="patient in filteredPatients" 
      :key="patient.id"
      class="patient-item"
    >
      {{ patient.name }}
    </div>
    
    <!-- Lazy loaded modal -->
    <Suspense>
      <PatientModal v-if="showModal" />
      <template #fallback>
        <div>Loading...</div>
      </template>
    </Suspense>
  </div>
</template>
```

### Build Performance

- Use **fast build script** for development: `./scripts/fast-build.sh`
- Optimize **Docker layers** for better caching
- **Minimize build context** with proper .dockerignore
- Use **parallel processing** where possible

## Security Considerations

### Input Validation

```php
// ✅ Good: Comprehensive validation
class StorePatientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s]+$/', // Only letters and accents
            ],
            'sus_number' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^\d{15}$/', // Exactly 15 digits
                'unique:patients,sus_number',
                new ValidSusNumber(), // Custom validation rule
            ],
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
                'unique:patients,email',
            ],
        ];
    }
}
```

### Authentication & Authorization

```php
// ✅ Good: Proper authorization
class PatientPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('create_patients') 
            && $user->isActive();
    }
    
    public function view(User $user, Patient $patient): bool
    {
        return $user->hasPermission('view_patients')
            && ($user->canAccessPatient($patient) || $user->isAdmin());
    }
    
    public function update(User $user, Patient $patient): bool
    {
        return $user->hasPermission('update_patients')
            && $user->canAccessPatient($patient);
    }
}
```

### File Upload Security

```php
// ✅ Good: Secure file handling
class EntryDocumentUploadService
{
    private const ALLOWED_TYPES = [
        'image/jpeg', 'image/png', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    public function uploadDocument(UploadedFile $file, Entry $entry): EntryDocument
    {
        // Validate file type
        if (!in_array($file->getMimeType(), self::ALLOWED_TYPES)) {
            throw new InvalidFileTypeException();
        }
        
        // Validate file size (10MB max)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new FileTooLargeException();
        }
        
        // Generate secure filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store in secure location
        $path = $file->storeAs(
            "documents/entries/{$entry->id}",
            $filename,
            'private'
        );
        
        return Document::create([
            'patient_id' => $patient->id,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }
}
```

## Community

### Getting Help

- **GitHub Discussions**: For questions and general discussion
- **Issues**: For bug reports and feature requests
- **Security**: Use private security reporting for vulnerabilities
- **Email**: For private inquiries (if available)

### Communication Guidelines

- **Be respectful** and professional
- **Search existing issues** before creating new ones
- **Provide detailed information** in bug reports
- **Follow up** on your contributions
- **Help others** when you can

### Recognition

We appreciate all contributions and will recognize contributors through:

- **Contributors list** in README
- **Release notes** acknowledgments
- **GitHub contributor insights**
- **Special mentions** for significant contributions

## Release Process

### Version Strategy

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Schedule

- **Patch releases**: As needed for critical bugs
- **Minor releases**: Monthly for new features
- **Major releases**: Quarterly or for significant changes

### Changelog

All notable changes are documented in [CHANGELOG.md](CHANGELOG.md) following the [Keep a Changelog](https://keepachangelog.com/) format.

---

## Questions?

If you have questions not covered in this guide:

1. **Check existing documentation** and issues
2. **Search discussions** for similar questions
3. **Create a discussion** with your question
4. **Ask in your pull request** if it's related to your contribution

Thank you for contributing to Lista da regulação! Together, we're building better healthcare technology for Brazil. 🏥

Made with ❤️ by Gustavo M. (https://github.com/guztaver)

**Last Updated**: January 2025