# Kingly Layouts

Kingly Layouts provides a suite of highly configurable, modern layouts for
Drupal's Layout Builder. Built with a flexible plugin system, it empowers site
builders to create visually rich page sections with fine-grained control over
spacing, colors, backgrounds, animations, and more, all through an intuitive
user interface.

## Features

* **Multi-Column Layouts**: Includes 1, 2, 3, and 4-column layouts with
  responsive sizing options (
  e.g., 50/50, 25/75, 33/34/33).
* **Modern Frontend**: Utilizes CSS Grid for robust, efficient, and predictable
  layout rendering
  across all modern browsers.
* **Rich Display Options**: A comprehensive set of controls to customize every
  aspect of a layout section.
* **Direct Color Input**: Instead of a predefined palette, colors (for
  foreground, background, borders, and gradients) are now directly entered
  using a color picker, providing full flexibility.
* **Dynamic Backgrounds**: Supports solid colors, gradients, images (with
  parallax), and self-hosted background videos.
* **On-Scroll Animations**: Animate sections as they enter the viewport using
  the performant Intersection Observer API.
* **Hover Effects**: Apply subtle visual transformations (scale, box-shadow,
  filters, **font size**) to layout sections on user hover.
* **Plugin Architecture**: Fully extensible system allowing developers to easily
  add new display option groups.
* **Granular Permissions**: Control which user roles can access each group of
  settings (
  e.g., allow editors to change colors but not spacing).

## Requirements

* Drupal Core Layout Discovery module (`drupal:layout_discovery`)

## Installation

1. Install the module as you would any other Drupal module. The recommended way
   is via Composer:
   ```bash
   composer require erikdekamps/kingly_layouts
   ```
2. Enable the module on the Extend page (`/admin/modules`).

## Configuration

Kingly Layouts is designed to be configured directly within Drupal's Layout
Builder
interface. There are no module-level configuration pages.

### Using Kingly Layouts in Layout Builder

1. When managing the layout for a content type or a custom page, click **Add
   section**.
2. In the right-hand sidebar, select one of the layouts prefixed with "Kingly" (
   e.g., "Kingly: Two column").
3. After adding the section, click **Configure section** on the section you just
   added.
4. The sidebar will now display all the available Kingly Layouts options,
   grouped into categories.

## Layout Options

The following configuration options are available for every Kingly layout
section.

### General

* **Column Sizing**: Controls the width distribution of the layout's columns (
  e.g., 50/50, 25/75, 33/34/33). The specific options depend on the chosen
  layout.
* **Container Type**:
* **Boxed**: Standard container with a maximum width.
* **Full Width (Background Only)**: The background spans the full viewport
  width, but the content remains
  aligned with the site's main content area.
* **Edge to Edge (Full Bleed)**: Both the background and content span the full
  viewport width.
* **Full Screen Hero**: The section fills the entire viewport height and width.

### Spacing

Control the space inside and around the layout using a consistent scale.

* **Horizontal & Vertical Padding**: Space *inside* the layout container.
* **Gap**: Space *between* columns within the layout.
* **Horizontal & Vertical Margin**: Space *outside* the layout container.

### Colors

* **Foreground Color**: Sets the text color for the entire section using a
  color picker. Enter a 6-digit hex code (e.g., `#RRGGBB`).

### Border

* **Border Color, Width, & Style**: Apply a border by entering a hex color code
  directly, along with a pre-defined thickness
  and style (solid, dashed, dotted).
* **Border Radius**: Round the corners of the layout, with options from `Small`
  to `Full (Pill/Circle)`.

### Alignment

* **Vertical Alignment**: Align the content of all columns vertically (Top,
  Middle, Bottom,
  Stretch). Especially useful for columns of different heights.
* **Horizontal Alignment**: Justify the content within the layout horizontally (
  e.g., Start/Left, Center, Space Between).

### Background

* **Background Type**: Choose between a solid color, an image, a video, or a
  gradient.
* **Color Options**: When 'Color' is selected, choose a background color using
  the color picker and set its opacity.
* **Media Options**: Provide a URL for a background image or video. For images,
  control
  position, repeat, size, and attachment (for parallax effects). For videos,
  control loop, autoplay, and mute settings.
* **Gradient Options**: Configure linear or radial gradients using two direct
  hex color inputs.
* **Overlay**: Add a semi-transparent color overlay on top of any background
  media to improve text readability, using a color picker.

### Shadows & Effects

* **Static Effects**:
* **Box Shadow**: Apply a pre-configured drop shadow to the layout container.
* **Filter**: Apply a CSS filter like `Grayscale`, `Blur`, or `Sepia`.
* **Opacity, Scale, Rotate**: Adjust the section's overall transparency and
  apply CSS transforms.
* **Hover Effects**:
* **Hover Scale**: Apply a scale transformation to the section on hover.
* **Hover Box Shadow**: Change the box shadow on hover.
* **Hover Filter**: Apply a filter change on hover (e.g., Grayscale to Color,
  Brightness adjustment).
* **Hover Font Size**: Change the font size of text within the section on hover.

### Animation

* **Animation Type**: Animate the section as it scrolls into view (
  e.g., `Fade In`, `Slide In`).
* **Transition Options**: Fine-tune the animation's direction, duration, speed
  curve, and delay.

### Responsiveness

* **Hide on Breakpoint**: Choose to hide the entire layout section on specific
  screen sizes (Mobile,
  Tablet, or Desktop).

### Custom Attributes

* **Custom ID**: Assign a unique HTML `id` attribute for CSS/JS targeting or
  anchor links.
* **Custom CSS Classes**: Add one or more custom classes for advanced styling.

## Permissions

Kingly Layouts provides a granular permission for each option group, allowing
you to control which roles can access which settings. This is useful for
preventing accidental changes and restricting complex features to trusted users.

All permissions can be found on the main permissions
page (`/admin/people/permissions`) under the "Kingly Layouts" section.

* **Administer Kingly Layouts: Column Sizing**
* **Administer Kingly Layouts: Container Type**
* **Administer Kingly Layouts: Spacing**
* **Administer Kingly Layouts: Colors**
* **Administer Kingly Layouts: Typography**
* **Administer Kingly Layouts: Border**
* **Administer Kingly Layouts: Alignment**
* **Administer Kingly Layouts: Animation**
* **Administer Kingly Layouts: Background**
* **Administer Kingly Layouts: Shadows & Effects**
* **Administer Kingly Layouts: Responsiveness**
* **Administer Kingly Layouts: Custom Attributes**
