## Assets plugin for Spress

![Spress 2 ready](https://img.shields.io/badge/Spress%21-ready-brightgreen.svg)

This plugin has the aim to bring the most features of [jekyll-assets](https://github.com/jekyll/jekyll-assets) to SPRESS. 

**This plugin requires Spress >= 2.2. 

### How to install?

Go to your site folder and input the following command:

```bash
$ spress add:plugin shdev/spress-plugin-assets
```

As as soft dependency you need [ImageMagick](http://www.imagemagick.org/script/index.php)

Under macOS I used [Homebrew](https://brew.sh/) to install it with
 
```bash
$ brew install imagemagick
```

### How to use?

Store your assets in `src/assets` with your folder structure 
 
For example this structure   
 
```
./src/assets/
|- img
   |- header.jpg
   |- feature-image.jpg
|- css
   |- style.css
|- js
   |- app.js
```

In `config.yml` you can set the following options.
Here the default values are displayed. 

```yaml
asset_output_path: 'build/assets'
asset_output_web_prefix: '/assets'
```

`asset_output_path` the path where the file is stored during building.

`asset_output_web_prefix` is the path prefix for the link.

For example if you use a CDN you want the assets in an own build-folder and the URL prefix contains information about your CDN.     

In your template you use the path relative to `src/assets` for your items with the asset filters.

#### Filter `asset_path`

This filter gives the path of   

```twig
{{ 'img/header.jpg' | asset_path }}
```

Renders the URL 

```
<asset_output_web_prefix>/img/header_813dec0e6ec1420d101c3f07bfc0a135.jpg
```

and writes the file 

``` 
<asset_output_path>/img/header_813dec0e6ec1420d101c3f07bfc0a135.jpg
```

You see the output file is enriched with a hash value.
It's a md5 sum which depends on the content of the file and some other information.

#### Filter `img`

```twig
{{ 'img/header.jpg' | img }}
```

Renders the HTML

```
<img src="<asset_output_web_prefix>/img/header_813dec0e6ec1420d101c3f07bfc0a135.jpg">
```

and writes the file 

``` 
<asset_output_path>/img/header_813dec0e6ec1420d101c3f07bfc0a135.jpg
```

#### Filter `css`

```twig
{{ 'css/style.css' | css }}
```

Renders the HTML

```
<link rel="stylesheet" href="<asset_output_web_prefix>/css/style_813dec0e6ec1420d101c3f07bfc0a135.css"/>
```

and writes the file 

``` 
<asset_output_path>/css/style_813dec0e6ec1420d101c3f07bfc0a135.css
```

#### Filter `js`

```twig
{{ 'js/app.js' | js }}
```

Renders the HTML

```
<script src="<asset_output_web_prefix>/js/app_813dec0e6ec1420d101c3f07bfc0a135.js"></script>
```

and writes the file 

``` 
<asset_output_path>/js/app_813dec0e6ec1420d101c3f07bfc0a135.js
```

#### Filter options for `img`|`css`|`js`

If you want to set some attributes to the tags use the `attr` options.
This options only effects the render output.

Example with img

```twig
{{ 'img/header.jpg' | img({attr:{class:'img-responsive', id:"header-image", alt:"Some importend text about the image"}}) }}
```

Renders the HTML

```
<img class="img-responsive" id="header-image" alt="Some importend text about the image" src="<asset_output_web_prefix>/img/header_813dec0e6ec1420d101c3f07bfc0a135.jpg">
```

#### Filter options for all tags if the file extension is `jpg`|`jpeg`|`png`|`gif`

Like the shining example [jekyll-assets](https://github.com/jekyll/jekyll-assets) it uses [ImageMagick](http://www.imagemagick.org/script/index.php) for image manipulation.

You can use it with every filter but it should only usefull with `img` and `asset_path`

This options of the `convert`-command are used:

* [quality](http://www.imagemagick.org/script/command-line-options.php#quality)
* [resize](http://www.imagemagick.org/script/command-line-options.php#resize)
* [crop](http://www.imagemagick.org/script/command-line-options.php#crop)
* [gravity](http://www.imagemagick.org/script/command-line-options.php#gravity) only if `crop` options is present.

Also when `crop` is used a `+repage` will appended, look here [repage](http://www.imagemagick.org/script/command-line-options.php#repage) for more information

Here an example with all options at once

```twig
{{ 'img/feature-image.jpg' | img({resize: '300x300^', crop: '300x300+0+0', gravity: 'SouthEast', quality:60 }) }}
```

This creates a new images which is quadratic.
 
* `resize: '300x300^'` means resize the image that it cover a *300px* times *300px* area
* `crop: '300x300+0+0'` means crop to *300px* times *300px* but 
* `gravity: 'SouthEast'` use the bottom-right corner
* `quality:60` reduce the quality to 60%. 

Renders the HTML

```
<img src="<asset_output_web_prefix>/img/feature-image_6fa6fa3af470d85af98ac7f7b8d5c933.jpg">
```

and writes the file 

``` 
<asset_output_path>/img/feature-image_6fa6fa3af470d85af98ac7f7b8d5c933.jpg
```

Be informed different image transformation options lead to other hash values used for the file.

So 

```twig
{{ 'img/feature-image.jpg' | img }}
```

will not create the file 

``` 
<asset_output_path>/img/feature-image_6fa6fa3af470d85af98ac7f7b8d5c933.jpg
```

### Cache folder

For a better performance the plugin cached all files in the folder `.cache/assets`.
This saved time for the image manipulation.
It will not clear old unused file.
So please clean it up from time to time.    

### asset boost on apache webserver 

As an extra you can asset boost your site if it runs with an apache webserver with the following `.htaccess` options.

```apacheconfig
RewriteEngine On

RewriteCond %{HTTP:Accept-Encoding} gzip
RewriteCond %{REQUEST_FILENAME}.gz -f
RewriteRule ^(.+\.(xml|html|css|js|eot|eot|woff2|woff|ttf|svg))$ /$1.gz [L]

<FilesMatch "\.(xml|xml.gz|html|htm|html.gz|htm.gz)$">
Header set Cache-Control "no-cache, no-store, must-revalidate"
Header set Pragma "no-cache"
</FilesMatch>

<FilesMatch "\.(gz)$">
Header set Vary "Accept-Encoding" 
Header set Content-Encoding "gzip"
</FilesMatch>

<FilesMatch "\.js.gz$">
Header set Content-Type "application/javascript"
</FilesMatch>

<FilesMatch "\.css.gz$">
Header set Content-Type "text/css; charset=UTF-8"
</FilesMatch>

<FilesMatch "_[a-f0-9]{32}\.(ico|pdf|flv|jpg|jpeg|png|gif|swf|ico.gz|pdf.gz|flv.gz|jpg.gz|jpeg.gz|png.gz|gif.gz|swf.gz|js|css|css.gz|js.gz)$">
ExpiresActive On
ExpiresDefault "access plus 1 year"
Header set Cache-Control "max-age=8916000, public"
</FilesMatch>
```

I generate the *gzip* files as an after build step.

## Wished features for the next releases

* a posteriori run of `jpegoptim` und `optipng`
* filter for outputing raw asset to twig. E.g. inline *js* or *css*
* filter for outputing transformed image to twig. E.g. inline base64 images
* asset boost configs for other web servers, I hope for *pull requests*
* render `css`|`js` or other files with twig before coping, this allows to inject e.g. colors from `config.yml` to the style files.
* scss processing
* babel processing

 