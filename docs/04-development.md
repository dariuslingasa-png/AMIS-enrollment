# Development

## Backend
The backend is Laravel-based and handles authentication, applicant data, enrollment submission, draft saving, file uploads, and payment submission.

## Frontend
The frontend uses Blade templates, Tailwind CSS, custom CSS files, Vite, and Alpine.js for lightweight interactivity.

## Important Folders
- `app/Http/Controllers`
- `app/Models`
- `database/migrations`
- `resources/views`
- `resources/css`
- `routes`
- `public/images`

## Backend Tasks
- Add or update migrations
- Create model relationships
- Validate form requests
- Store uploaded documents
- Add applicant status transitions
- Add payment verification logic

## Frontend Tasks
- Improve Blade screens
- Maintain shared layouts
- Update shared CSS
- Add responsive behavior
- Improve loading states
- Keep form components reusable

## Build Commands
```bash
npm run build
php artisan serve
```

## Development Notes
- Prefer existing Blade and CSS patterns.
- Keep UI changes scoped to the relevant CSS file.
- Rebuild assets after CSS or JS changes.
- Do not hardcode route URLs when named routes exist.
