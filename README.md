# Page Builder plugin for Craft CMS 3.x

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Add the following to your Craft project's `composer.json`:

        "repositories": [
          {
            "type": "git",
            "url":  "https://bitbucket.org/thepixelage/craft-pagebuilder"
          }
        ]


2. Generate `auth.json` file for authenticating with Bitbucket:

        composer config bitbucket-oauth.bitbucket.org <consumer-key> <consumer-secret>

    Note: Make sure `auth.json` is added to `.gitignore` and do not check in this file.

3. Then tell Composer to load the plugin:

        composer require thepixelage/craft-pagebuilder

    Note: You may be prompted to enter your Bitbucket credentials.

4. Install the plugin by running this command in the CLI:

        ./craft plugin/install page-builder

    Note: Other plugin dependencies (e.g. Neo, Super Table, Redactor) need to be installed but not yet activated when installing Page Builder. Make sure these plugins are installed and activated before installing Page Builder.

## Bootstrap Page Builder fields

To bootstrap the Page Builder default fields, following these instructions.

1. Copy the project config YAML files from `src/config/project` in the plugin's folder.

2. Run the following command to force the `project.yaml` file to be updated:

        touch config/project/project.yaml

3. Go to Control Panel in the browser and update/sync the project config accordingly.

## Display output from Page Builder field

To output the contents of the Page Builder field, use the following:

    {% set vars = {
        'pageBuilderField': entry.tpbPageBuilder
    } %}
    {{ include('_pagebuilder', vars) }}

## Override sample templates

1. Create a `_pagebuilder` folder under the Craft project's `templates` folder.

2. Create the corresponding template files to override. For example, to override the template for Text block, create the file `_pagebuilder/text/index.twig`.
