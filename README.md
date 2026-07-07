# SignNow PHP Sample App

[![PHP](https://img.shields.io/badge/php-8.2-blue)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-10-cyan)](https://laravel.com/)
[![SignNow SDK](https://img.shields.io/badge/SignNow_SDK-3.x-light)](https://github.com/signnow/SignNowPHPSDK)
[![License](https://img.shields.io/badge/license-MIT-green)](./LICENSE)

A Laravel 10 application demonstrating the SignNow API via the official [`signnow/api-php-sdk`](https://github.com/signnow/SignNowPHPSDK) package.

## Quick Start

### 1. Enter the directory

```bash
cd SampleApps/php-sample-app
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and fill in your SignNow credentials:

| Variable | Example | Description |
|---|---|---|
| `SN_API_HOST` | `https://api.signnow.com` | Production or sandbox URL |
| `SN_API_BASIC_TOKEN` | `c2lnbk5vdy4...` | Base64 token from [API Dashboard](https://app.signnow.com/webapp/api-dashboard/keys) |
| `SN_API_USER` | `you@example.com` | Your SignNow account email |
| `SN_API_PASSWORD` | `••••••` | Your SignNow account password |
| `SN_SIGNER_EMAIL` | `signer@example.com` | Default embedded signer email |

### 3. Run with Docker (recommended)

```bash
make build    # build Docker image
make setup    # install dependencies + generate Laravel key
make up       # start the container
```

App is available at `http://localhost:8080`. Stop with `make down`.

Other useful targets:

```bash
make clear        # clear Laravel caches
make setup-env    # interactive credential prompt
```

### 4. Run with Docker (without make)

```bash
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

### 5. Run locally (no Docker)

```bash
composer install
php artisan key:generate
php artisan serve --port=8080
```

Requires PHP 8.2+ with the `curl`, `mbstring`, `openssl`, and `xml` extensions.

### 6. Open a sample

```
http://localhost:8080/samples/EmbeddedSignerConsentForm
```

`http://localhost:8080/samples` lists all available samples.

## Available Samples (20)

| Sample | Description |
|---|---|
| EmbeddedSignerConsentForm | Consent form with single embedded signer |
| EmbeddedSenderWithoutFormFile | Sales proposal — embedded sender |
| EmbeddedSenderWithFormCreditLoanAgreement | Credit loan agreement |
| EmbeddedSignerConsumerServices | Veterinary intake form |
| EmbeddedSignerPatientIntakeForm | Patient intake (healthcare) |
| EmbeddedSignerWithFormInsurance | Insurance claim form |
| MedicalInsuranceClaimForm | Medical insurance claim |
| EmbeddedEditingAndSigningDG | Document generation: edit + sign |
| EmbeddedSenderWithFormAndFirstSigner | Sender is also first signer |
| EmbeddedSenderWithFormDG | Document generation variant |
| EmbeddedSenderWithFormDGAdjunct | DG adjunct form |
| EmbeddedSenderWithFormDGConstr | DG construction form |
| ISVWithFormAndOneClickSendBasicPrefill | ISV one-click send, basic prefill |
| ISVWithFormAndOneClickSendMergeFields | ISV one-click send, merge fields |
| HROnboardingSystem | HR onboarding multi-document flow |
| UploadEmbeddedSender | Upload PDF + embedded sender |
| PrefillAndEmbeddedSendingAgreement | Prefill + embedded send |
| PrefillAndOneClickSendingAgreement | Prefill + one-click send |
| EVDemoSendingAnd3EmbeddedSigners | Real estate: 3 sequential embedded signers |
| UploadEmbeddedEditingAndInvite | Upload PDF, embedded edit, invite |

## Project Structure

```
app/Http/Controllers/
  RoutingController.php         GET /samples/:name, POST /api/samples/:name
  SampleControllerInterface.php Sample contract (handleGet + handlePost)
  ControllerFactory.php         Loads sample controllers by name
app/Services/
  SamplesProvider.php           Validates names against whitelist
routes/
  web.php                       Route definitions
samples/
  <SampleName>/
    SampleController.php        Implements SampleControllerInterface
    Meta.json                   Sample metadata (title, description)
    index.blade.php             Blade template served on GET /samples/<SampleName>
resources/views/                Shared Blade layouts
static/                         Shared CSS, JS, images
samples_witelist.json           Authorised sample names
```

## Routing

| Method | Path | Handler |
|---|---|---|
| GET | `/samples/{name}` | `samples/<name>/SampleController.handleGet` |
| POST | `/api/samples/{name}` | `samples/<name>/SampleController.handlePost` |
| GET | `/css/*`, `/img/*`, etc. | Shared static files |

Sample names must match `^[a-zA-Z0-9_]+$` and must be listed in `samples_witelist.json`. Unlisted names → 404.

## Add a New Sample

1. `mkdir samples/MyNewSample`
2. Add `"MyNewSample"` to `samples_witelist.json`.
3. Create `samples/MyNewSample/Meta.json`:
   ```json
   { "title": "My New Sample", "description": "What it demonstrates." }
   ```
4. Create `samples/MyNewSample/SampleController.php`:
   ```php
   <?php
   namespace Samples\MyNewSample;

   use App\Http\Controllers\SampleControllerInterface;
   use Illuminate\Http\Request;

   class SampleController implements SampleControllerInterface
   {
       public function handleGet(Request $request) { /* return view or JSON */ }
       public function handlePost(Request $request) { /* handle POST actions */ }
   }
   ```
5. Create `samples/MyNewSample/index.blade.php`.

## Tests

No automated tests are included. The application can be verified manually by running each sample end-to-end.

## SDK Notes

Known quirks in `signnow/api-php-sdk` 3.x that affect this codebase:

**`To` constructor parameter order** — `new To(email, roleId, role, order, subject, message)`. Note that `roleId` (the `unique_id` from the roles array) and `role` (the role name string) are separate parameters in positions 2 and 3.

**`SendInvitePost` constructor** — `new SendInvitePost(documentId, ToCollection, from, subject, message)`. The sender email (`from`) must be the authenticated account's email.

**Embedded sending vs embedded signing mode** — `DocumentEmbeddedSendingLinkPost` takes a `mode` parameter: `'document'` opens the embedded sender UI; `'invite'` opens the embedded signer UI. Passing the wrong mode is a silent failure — the link works but shows the wrong interface.

**`DocumentGet` response roles** — `getRoles()` returns an array where each role has `getName()` (display name) and `getUniqueId()` (the ID to pass as `roleId` in invites). Do not confuse the two.

## Tech Stack

- PHP 8.2
- Laravel 10
- `signnow/api-php-sdk` 3.x (from Packagist)
- Guzzle 7.9 (HTTP client, pulled in by SDK)
- Docker: `php:8.2-fpm` + nginx

## GitHub Copilot Extension

Get AI-powered SignNow code suggestions in your IDE:
[github.com/apps/signnow](https://github.com/apps/signnow) — start prompts with `@signnow`.

## License

See [LICENSE](./LICENSE).
