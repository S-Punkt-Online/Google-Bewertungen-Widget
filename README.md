# Contao Google Reviews Widget

Google Reviews Widget for Contao CMS with support for:

- Frontend Module
- Content Element
- Google Places API
- Schema.org structured data
- Local JSON caching

---

## Features

- Display Google ratings
- Average star rating
- Total review count
- Link to Google reviews
- Local caching
- Automatic hide if no reviews available
- Usable as:
  - Frontend Module
  - Content Element

---

# Installation

## Via Contao Manager

Search for:

```bash
s-punkt-online/contao-google-reviews-widget
```

Install the package and run the database migration.

---

## Via Composer

```bash
composer require s-punkt-online/contao-google-reviews-widget
```

Then clear the cache:

```bash
vendor/bin/contao-console cache:clear
```

---

# Google API Setup

## 1. Create Google Cloud Project

Open Google Cloud Console:

https://console.cloud.google.com

---

## 2. Enable Places API

Enable:

```text
Places API
```

Important:

Do not only enable `Places API (New)`.
This extension currently uses the classic `Places API`.

---

## 3. Create API Key

Open:

```text
APIs & Services → Credentials
```

Create a new API key.

---

## 4. Configure API Key

### Application restrictions

For testing:

```text
None
```

Later recommended:
- IP restriction for your server

Important:

Do not use HTTP Referrer restrictions because the API requests are executed server-side via PHP.

---

### API restrictions

Allow:

```text
Places API
```

---

# Find Google Place ID

Use:

https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder

---

# Usage as Frontend Module

Backend:

```text
Themes → Frontend Modules
```

Module type:

```text
Google Reviews Widget
```

---

# Usage as Content Element

Backend:

```text
Articles → New Element
```

Element type:

```text
Google Reviews Widget
```

---

# Available Fields

## Google API Key

Your Google API key.

---

## Google Place ID

Google Place ID of the business.

---

## Business Name

Used for Schema.org structured data.

Example:

```text
Blue Detect GmbH
```

---

## Business URL

Example:

```text
https://www.example.com
```

---

## Google Review URL

Direct link to Google reviews.

---

# Cache

Google API responses are stored locally.

Path:

```text
public/files/google-review-cache/
```

Benefits:
- Better performance
- Fewer API requests
- Lower Google API costs

---

# Template Override

Template:

```text
ce_google_reviews_widget.html5
```

Can be overridden inside your Contao project.

---

# CSS & JavaScript

Assets are loaded automatically.

Files:

```text
public/css/google-reviews-widget.css
public/js/google-reviews-widget.js
```

---

# Requirements

- Contao 5.3+
- PHP 8.1+
- Enabled Google Places API

---

# Support

S Punkt Online GmbH

https://www.s-punkt-online.de