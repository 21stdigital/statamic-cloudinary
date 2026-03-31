# Cloudinary

> Cloudinary is a Statamic addon that allows you to use [Cloudinary](https://cloudinary.com) to deliver your assets.

## Features

This addon allows you to:

- leverage the transformation and delivery power of Cloudinary
- use images and videos with the same tag patterns
- rely on Cloudinary auto-upload mapping so media can be delivered from Cloudinary after the first request

## Requirements

- Statamic `^5.73.11` or `^6.4` (same constraint as this package; PHP and Laravel versions follow your Statamic project)

## How to Install

Run the following command from your project root:

```bash
composer require tfd/statamic-cloudinary
```

Supported Statamic versions: `5.73.11+` and `6.6.2+`.

## How to Use

### 1. Create a Cloudinary Account

Go to https://cloudinary.com and create a free account.  
It is recommended to create a new folder inside Cloudinary's Media Library that hosts all the media of your website.

Go to `Settings > Upload > Auto upload mapping` and fill out

- Folder: Your newly create folder
- URL prefix: Your website's URL, including a **trailing slash**

Grab the following information from your cloudinary Dashboard:

- Cloud name
- API Environment variable

### 2. Publish the cloudinary configuration file

Run the following command from your project root:

```bash
php artisan vendor:publish --tag="cloudinary-config"
```

This will create a `cloudinary.php` file inside `config/statamic`.

### 3. Setup Cloudinary variables

Enter at least the following data in `config/statamic/cloudinary.php` or use your project's `.env` file.  
The `auto_mapping_folder` is the folder you've created during the first step.  
The `url` is the API Environment variable you've copied from the first step.

```php
<?php

return [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', null),
    'auto_mapping_folder' => env('CLOUDINARY_AUTO_MAPPING_FOLDER', ''),
    'url' => env('CLOUDINARY_URL', ''),
];
```

```bash
CLOUDINARY_CLOUD_NAME=xxx
CLOUDINARY_AUTO_MAPPING_FOLDER=xxx
CLOUDINARY_URL=cloudinary://xxx:xxx@xxx
```

The published config file also includes optional keys used for uploads, delivery type, and defaults, for example:

- `upload_url` — base upload/delivery URL (default `https://res.cloudinary.com/`)
- `api_key`, `api_secret`, `upload_preset`, `notification_url` — for signed uploads or webhooks if you use them
- `delivery_type` — defaults to `upload`; adjust if your delivery setup differs

See the full `config/statamic/cloudinary.php` after publishing for all keys and defaults.

#### 3.1. Using an External URL

If your assets are served through an external URL, you need to set an additional configuration option, either in the `cloudinary.php` config file:

```php
<?php

return [
  // ...
  'external_url_prefix' => env('CLOUDINARY_EXTERNAL_URL_PREFIX', 'https://my-external-asset-url.com'),
];
```

... or in the `.env` file:

```bash
CLOUDINARY_EXTERNAL_URL_PREFIX=https://my-external-asset-url.com
```

For example, if you are using DigitalOcean Spaces storage and your assets are served from this URL:  
`https://my-project.us1.digitaloceanspaces.com/website/image.jpg`, the external URL consists of two parts:

- `https://my-project.us1.digitaloceanspaces.com` - The DigitalOcean base URL
- `/website` - The root folder in DigitalOcean

The actual value you need to set in the config or .env file would be:  
`CLOUDINARY_EXTERNAL_URL_PREFIX=https://my-project.us1.digitaloceanspaces.com/website/`

### 4. Use the Cloudinary tag

You are now ready to use the Cloudinary tag inside your views.

If Cloudinary is not configured correctly, the tag falls back to Statamic's Glide-based image handling. Failed asset lookups in non-pair tag usage may also fall back to Glide.

#### Some examples

```html
<img src="{{ cloudinary:image }}" />
```

The image is transformed according to the default transformation parameters, see step 5.

---

```html
<img src="{{ cloudinary:image width="800" height="500" }}" />
```

The image is resized to 800 x 500 px.

---

```html
{{ cloudinary:image width="400" height="300" }}
<img src="{{ url }}" width="{{ width }}" height="{{ height }}" />
{{ /cloudinary:image }}
```

Alternative usage with tag pairs.

---

```html
{{ cloudinary:image width="400" }}
<img src="{{ url }}" width="{{ width }}" height="{{ height }}" />
{{ /cloudinary:image }}
```

If you only provide width or height when using the tag pair, the addon automatically calculates the other dimension depending on the image's aspect ratio and makes this value available in your view.

---

```html
<video src="{{ cloudinary:video width="600" height="400" effect="blur:1000" }}" muted autoplay></video>
```

Videos are also supported. Depending on the video size the initial (automatic) upload to cloudinary might take some time.

---

```html
<img src="{{ cloudinary:image width="800" height="500" quality="10"
effect="blur:800" opacity="20" }}" alt="">
```

Usage with other parameters.

---

```html
<x-cloudinary-image
  :src="$featured_image"
  height="422"
  alt="Das ist ein Test"
  effect="sepia:80"
/>
```

There is also a custom Blade component to use Cloudinary in your Blade templates. The `src` attribute is required. If Cloudinary is not configured or `src` is missing, the component outputs nothing. The current component alias is `<x-cloudinary-image />`; the legacy `<x-image />` alias and published `components/image.blade.php` override remain supported for backward compatibility.

### 5. Available parameters

For more information about these parameters, head over to the [cloudinary documentation](https://cloudinary.com/documentation/transformation_reference).

| Parameter            |
| -------------------- |
| angle                |
| aspect_ratio         |
| background           |
| border               |
| crop                 |
| color                |
| dpr                  |
| duration             |
| effect               |
| end_offset           |
| flags                |
| height               |
| overlay              |
| opacity              |
| quality              |
| radius               |
| start_offset         |
| named_transformation |
| underlay             |
| video_codec          |
| width                |
| x                    |
| y                    |
| zoom                 |
| audio_codec          |
| audio_frequency      |
| bit_rate             |
| color_space          |
| default_image        |
| delay                |
| density              |
| fetch_format         |
| format               |
| gravity              |
| prefix               |
| page                 |
| video_sampling       |
| progressive          |

### 6. Adjust default parameters

You can adjust the default parameters to your likings in the `config/statamic/cloudinary.php` configuration file:

```php
return [
  'default_transformations' => [
    'image' => [
      'crop' => 'fill',
      'dpr' => 'auto',
      'fetch_format' => 'auto',
      'gravity' => 'auto',
      'quality' => 'auto',
    ],
    'video' => [
      'crop' => 'fill',
      'dpr' => 'auto',
      'fetch_format' => 'auto',
      'quality' => 'auto',
    ],
  ],
];
```

### 7. Publish the cloudinary views

Run the following command from your project root:

```bash
php artisan vendor:publish --tag="cloudinary-views"
```

This will publish the Cloudinary views to the `resources/views/vendor/cloudinary` folder.
