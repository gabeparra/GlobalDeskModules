# GlobalDeskModules

This repository now contains a custom FreeScout module named **ApiBridge**.  
The module replaces the stock API/Webhooks package with a leaner, maintainable
implementation that keeps API access and outbound webhooks in sync.

## Contents

- `ApiBridge/` – new FreeScout module that exposes REST endpoints and webhook dispatching.
- `backend/` – reserved for server-side utilities (unchanged).

## Installing ApiBridge

1. Copy or symlink `ApiBridge` into your FreeScout installation, e.g.
   ```
   ln -s /home/gabriel/projects/GlobalDeskModules/ApiBridge /var/www/freescout/Modules/ApiBridge
   ```
2. From the FreeScout root run:
   ```
   composer dump-autoload
   php artisan module:migrate ApiBridge
   ```
3. Activate **API Bridge** from the Modules screen inside FreeScout.

## Features

- REST API endpoints for conversations and customers under `/api/apibridge`.
- API key management with UI-based regeneration.
- Configurable CORS hosts.
- Webhook definitions stored in dedicated tables with retry/backoff support.
- Background command queue for failed webhook retries and log pruning.

## Usage

1. Visit **Settings → API Bridge** inside FreeScout.
2. Regenerate the API key and share it with trusted integrations.
3. Configure allowed CORS origins if you call the API from browsers.
4. Create webhooks mapped to conversation/customer events.
5. Use the API endpoints with header `X-Api-Key: <your-key>`.

## Maintenance

- Queue workers will pick up webhook retry jobs triggered by
  `apibridge:webhooks-process`.
- Clean up historical logs by running `apibridge:webhooks-clean-logs`.

See the in-app settings view for the complete list of events and configuration
options.