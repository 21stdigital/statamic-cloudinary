# Cloudinary

> Cloudinary is a Statamic addon that allows you to use cloudinary to deliver your assets.

## Features

This addon allows you to:

- leverage the transformation and delivery power of cloudinary
- usable with images and videos
- automatically upload media to cloudinary

## How to Install

Run the following command from your project root:

```bash
composer require tfd/statamic-cloudinary
```

## How to Use

### 1. Create a Cloudinary Account

Go to https://cloudinary.com and create a free account.  
It is recommended to create a new folder inside cloudinary's Media Library that hosts all the media of your website.  

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

### 3. Setup cloudinary variables

Enter at least the following data in the cloudinary.php file or use your project's .env file.  
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

### 4. Use the cloudinary tag

You are now ready to use the cloudinary tag inside your views.

#### Some examples

```html
<img src="{{ cloudinary:image }}">
```
The image is transformed according to the default transformation parameters, see step 5.

---

```html
<img src="{{ cloudinary:image width="800" height="500" }}">
```
The image is resized to 800 x 500 px.

---

```html
{{ cloudinary:image width="400" height="300" }}
  <img src="{{ url }}" width="{{ width }}" height="{{ height }}">
{{ /cloudinary:image }}
```
Alternative usage with tag pairs.

---

```html
{{ cloudinary:image width="400" }}
  <img src="{{ url }}" width="{{ width }}" height="{{ height }}">
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
<img src="{{ cloudinary:image width="800" height="500" quality="10" effect="blur:800" opacity="20" }}" alt="">
```
Usage with other parameters.

### 5. Available parameters

For more information about these parameters, head over to the [cloudinary documentation](https://cloudinary.com/documentation/transformation_reference).

| Parameter  |
| ------------- |
| angle  |
| aspect_ratio  |
| background |
| border |
| crop |
| color |
| dpr |
| duration |
| effect |
| end_offset |
| flags |
| height |
| overlay |
| opacity |
| quality |
| radius |
| start_offset |
| named_transformation |
| underlay |
| video_codec |
| width |
| x |
| y |
| zoom |
| audio_codec |
| audio_frequency |
| bit_rate |
| color_space |
| default_image |
| delay |
| density |
| fetch_format |
| gravity |
| prefix |
| page |
| video_sampling |
| progressive |

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
