## Task scope
Create WordPress plugin that draws markers on map using [Mapbox GL JS](https://docs.mapbox.com/mapbox-gl-js/api/), allows visitors to search/filter markers by tags and to add their own markers.

- It must be [SPA](https://en.wikipedia.org/wiki/Single-page_application), no page reload at all.
- Guests and logged in users should be able to view markers on map, filter markers by tag(s) and view marker details.
- Logged in users can add new markers.

![](https://raw.githubusercontent.com/skosm/agentfire-test/master/doc/images/main.png)

![](https://raw.githubusercontent.com/skosm/agentfire-test/master/doc/images/modal.png)

### Back-end
1. Admin page with settings, created via [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) (*acf_add_local_field_group*)
2. [WP REST](https://developer.wordpress.org/rest-api/) custom endpoint

### Settings page
1. Text field for [Mapbox token](https://docs.mapbox.com/help/how-mapbox-works/access-tokens/)

### Front-end
1. Front-end part is rendered using [Twig](https://twig.symfony.com/) templates
2. Layout and JS based on Bootstrap
3. UI interactions loaded via AJAX from custom WP REST endpoint (*not wp-ajax!*)
4. CSS/SCSS is up to you, you can use gulp/grunt/webpack to build assets

# Task details
1. **Base Level** (*required*)
	- Use shortcode `[agentfire_test]` to print plugin content
	- Plugin content blocks (see mockups)
		- Mapbox with markers
		- Filters
		- Use your best judgement to make it awesome [style is optional]
	- For logged in users click on map opens modal with settings for new marker - name (required), tags (add new tag - optional). Would be great if you use [select2](https://select2.org/) or [chosen](https://harvesthq.github.io/chosen/) to select/add tags.
	- Save marker as custom post type, tags as custom taxonomy (post edit in admin not needed).
	- Click on marker that shows marker details: name and tags, date added.
2. **Advanced level** (*optional*)
	- Add search field to search by marker name (name field in add modal)
	- Highlight another color markers added by the current WP user
	- Add filter for `My Markers` / `All Markers`
3. **Theory** (*no code required, just explanation*)
	- How you'd change/optimize the architecture of this plugin if the number of saved markers is 1M? 100M?

## Workflow
1.  Use any WordPress you want, single or multisite, version 4.9+
2.  Clone this repository
3.  Run `composer install` and `bower install` in plugin folder
4.  Push result to public repository on bitbucket/github and send us link.

## Coding guidelines
1.  PHP (7.0+) code must match [WordPress PHP Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
2.  It must follow patterns from the wireframe plugin - specification for autoloading, namespaces, strict types (if specified), each method must specify arguments and return types (except void) etc
3.  Add [PHPDoc](https://docs.phpdoc.org/references/phpdoc/index.html) comments for classes/methods, you can comment your code but it's not reuqired.
3.  3rd party assets included via Bower, Composer and NPM
4.  JavaScript should be IE10 compatible (which means ES5, or compiled)
5.  Feel free to use all modern CSS features, but better use bootstrap classes.

# Changes

### June 4, 2019
- Simplified task, updated task description.

### Aug 22, 2022
- Simplified task