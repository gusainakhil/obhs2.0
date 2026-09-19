# Complete report PDF API

This endpoint converts the existing `download-all-reports-pdf.php` report to a
downloadable PDF without modifying the existing report or the project's root
Composer dependencies.

## Request

Log in through `api/login.php` first and retain its `PHPSESSID` cookie.

```text
POST /api/download-all-reports-pdf.php
Content-Type: application/json
Cookie: PHPSESSID=your-login-session-id
```

```json
{
  "train_up": "11123",
  "train_down": "11124",
  "grade": "D - Thursday",
  "from": "2026-09-01",
  "to": "2026-09-14",
  "station_id": 25
}
```

`station_id` is optional. When supplied, it must match the logged-in station.
The API also accepts GET query parameters and POST form data. For browser GET
requests, use `up`, `down`, `grade`, `from_date`, and `to_date`.

Success returns `application/pdf` with a download attachment filename. API
errors return JSON. In Postman, use **Send and Download**. JavaScript clients
should read the response as a Blob.

## Isolated PDF runtime

The PDF dependency is stored under `api/pdf-runtime`. The root `composer.json`,
root `composer.lock`, root `vendor`, existing report, calculations, and UI are
not changed. When deploying without the included isolated vendor directory, run:

```bash
composer install --no-dev --working-dir=api/pdf-runtime
```
