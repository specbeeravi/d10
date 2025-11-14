## INTRODUCTION

The goal of this module is to provide a quick interface to test single directory
components without having to actually create content on the site.

In an ideal world, front end developers can build the markup, styles and
behaviors for the components and showcase without fully integrating them into
Drupal, similar to Patternlab or Storybook.

## REQUIREMENTS

* Drupal 10
* Single Directory Components

## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

## USAGE
* Go to /styleguide/explorer
* Navigate using the component explorer

## STORIES (DEMOS)

Stories allow you to have preset values to verify how a component would behave
with different values on their props and slots.

Stories follow a name convention like `component_name.story.YOUR_STORY_MACHINE_NAME.yml`.

A story structure defines some general info and the replacement values for props
and slots. You can check the demos on the `sdc_styleguide_demo_components`
submodule to get familiar. Also, there's the `` drush command. Finally, you
can export a demo when using the form functionality within the explorer.

## OVERRIDING STORIES

A story defined by an SDC might not fully work for your theme (for whatever reason),
if that is the case, you can override the theme on your active theme.

An example of this that has been experienced in the past is a branding component.
The parent theme that defines it might use the site slogan and the site name, but
your actual theme doesn't need those. Having those on the demo parent demo will
make your theme look broken, or you would need to add extra CSS to hide those for
the demo. Instead of doing that, just overriding the story file will be enough.

*You don't need to replace the SDC*, you just need to create a copy of the story
file with the same folder structure where the SDC is defined. For instance, if
your SDC is `modules/custom/my_module/components/my_sdc/my_sdc.component.yml`
and your story file is `modules/custom/my_module/components/my_sdc/my_sdc.story.my_demo.yml`
you can create `themes/custom/my_theme/components/my_sdc/my_sdc.story.my_demo.override.yml`
and this will override the demo data to match your theme needs (as long as my_theme
is your active theme).

## MAINTAINERS

Current maintainers for Drupal 10:

- Alejandro Madrigal (alemadlei) - https://www.drupal.org/u/alemadlei
