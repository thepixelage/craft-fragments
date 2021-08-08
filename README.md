# Fragments Plugin for Craft CMS

Fragments is a Craft CMS plugin for managing content and display fragments.

There are many situations where 

- 

**Fragments** aim to solve this problem by providing these entities that can streamline the  management of these ad-hoc fragments in a structured way similar to Sections and Entries, without ....

- Fragment types
- Zones
- Fragments

## Documentation

Learn more and read the documentation at https://www.thepixelage.com/plugins/fragments.

## License

This plugin requires a commercial license purchasable through the [Craft Plugin Store](https://plugins.craftcms.com/fragments).


## Requirements

This plugin requires Craft CMS 3.6.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Fragments”. Then click on the “Install” button in its modal window.

### With Composer

Open your terminal and run the following commands in your project directory:

```
# tell Composer to load the plugin
composer require thepixelage/craft-fragments

# tell Craft to install the plugin
./craft install/plugin fragments
```
## Setup

Before you can start creating fragments, you need to set up the fragment types and template zones. The **Fragments** section will only appear after at least one fragment type and one template zone are created.

### Fragment Types

To create a new fragment type, go to **Settings → Fragments → Fragment Types**.

Fragment types are similar to how entry types are set up. Custom fields can be specified to give a structured content model for the fragments.

### Template Zones

To create a new template zone, go to **Settings → Fragments → Zones**.

Template zones allow you to specify areas in your templates where fragments can be created and displayed in.
> **Tip:** Template zones can limit the fragment types that are allowed to be created in them. This helps make it easier to organise and manage fragments in the zones.

---

Created by [ThePixelAge](https://www.thepixelage.com)
