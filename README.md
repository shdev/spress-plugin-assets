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



##### Resize


