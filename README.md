<p><img src="https://www.thepixelage.com/plugins/assets/icon-fragments.svg" alt="Fragments icon" width="120"></p>

# Fragments Plugin for Craft CMS

**Fragments** is a Craft CMS plugin for managing content and presentational 
fragments. It allows you to decouple your entry types from content 
blocks, widgets or components that are ad-hoc or presentational in nature.

## Fragments

With **Fragments** comes a set of entities to help you manage content and 
presentational fragments:

- **Fragment types**  
  Just like entry types, you can set up the content model and field layout
  for your fragments.
  
  
- **Zones**  
  Template zones, or simply zones, are areas in your templates in 
  which fragments will be created and displayed. Zones can be limited to allow 
  only specific fragment types.
  
  
- **Fragments (element)**  
  These are the actual content blocks to be displayed. Each 
  fragment can be further customised to be included or excluded in the zones 
  depending on visibility rules.
  
  
- **Fragments (custom field)**  
  The included Fragments field allows your content editors to relate to 
  Fragments in some or all of the zones. This opens up possibility to create 
  reuseable fragments that can be included in entries and content block builders.

## Why Fragments?

There are many situations where fragments of content need to be displayed in
multiple pages, but they are ad-hoc content parts or data that may not have 
direct relations to your entry types. For example, your content admins may need 
to display messages or call-to-action buttons across entire websites or on only 
some pages.

Without **Fragments**, you may use features in Craft CMS to cobble together a
solution, but they bring along some problems:

- **Sections and entry types**  
  Entry types are good for mapping domain models
  to content models, but for content parts that have no direct relations to
  these domain models, it may become awkward to represent them as part of the
  content models, or an overkill to create whole sections just to represent
  them.


- **Globals**  
  Globals are usually used for content that do not belong as
  entries or are needed across different pages. However, in multi-site setups,
  using globals becomes difficult for managing different content on
  different sites, since all global sets are visible in the control panel for 
  all the sites. This can create confusion for your content editors.


- **Content Blocks/Content Builder (Matrix/Neo)**  
  Content block builder fields can become cluttered with too many block types. 
  **Fragments** can be used as a subsystem with its own types and zones (think 
  of them as groups or collections) that makes your content block builder 
  fields more scalable.

## Documentation

Learn more and read the documentation at 
https://www.thepixelage.com/plugins/fragments.

## License

This plugin requires a commercial license purchasable through the [Craft Plugin 
Store](https://plugins.craftcms.com/fragments).


## Requirements

This plugin requires Craft CMS 3.6.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for 
“Fragments”. Then click on the “Install” button in its modal window.

### With Composer

Open your terminal and run the following commands in your project directory:

```
# tell Composer to load the plugin
composer require thepixelage/craft-fragments

# tell Craft to install the plugin
./craft install/plugin fragments
```
## Setup

Before you can start creating fragments, you need to set up the fragment types 
and template zones. The **Fragments** section will only appear after at least 
one fragment type and one template zone are created.

### Fragment Types

Fragment types are similar to how entry types are set up. Custom fields can be
specified to give a structured content model for the fragments.

To create a new fragment type, go to **Settings → Fragments → Fragment Types**.

### Zones

Template zones (or zones, as we simply call it) allow you to specify areas in 
your templates where fragments can be created and displayed in. Zones can also 
limit the fragment types that are allowed to be created in them. This helps 
make it easier to organise and manage fragments  in the zones.

To create a new template zone, go to **Settings → Fragments → Zones**.

## Querying Fragments

To query a list of fragments in a zone to display in your templates:

```
{% set fragments = craft.fragments.zone('myZoneHandle').all() %}
```

Once you have queried the list of fragments, you can access the fields the same 
way you do for Entries.

```
{% for fragment in fragments %}
    <h1>{{ fragment.textField }}</h1>
    <img src="{{ fragment.imageField.one().url() }}" />
{% endfor %}
```

To target only fragments of a certain fragment type, modify the previous query 
like this:

```
{% set fragments = craft.fragments.zone('myZoneHandle').type('myFragmentTypeHandle').all() %}
```

---

Created by [ThePixelAge](https://www.thepixelage.com)
