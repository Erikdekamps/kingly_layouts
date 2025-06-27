# Kingly Layouts

A Drupal module that provides highly configurable layouts with a rich set of
display options.

## Layout Options

Each layout section comes with a comprehensive set of options to control its
appearance and behavior, grouped into the following categories.

### General

* **Column Sizing**: Controls the width distribution of the layout's columns (
  e.g., 50%/50%, 33%/67%). The specific options depend on the chosen layout.
* **Container Type**:
* **Boxed**: Standard container with a maximum width.
* **Full Width (Background Only)**: The background spans the full viewport
  width, but the content remains boxed.
* **Edge to Edge (Full Bleed)**: Both the background and content span the full
  viewport width.
* **Full Screen Hero**: The section fills the entire viewport height and width.

### Spacing

Control the space inside and around the layout. Options generally range
from `None` to `Extra Large`.

* **Horizontal & Vertical Padding**: Space inside the layout container.
* **Gap**: Space between columns within the layout.
* **Horizontal & Vertical Margin**: Space outside the layout container.

### Colors

* **Background & Foreground Color**: Select from a predefined list of colors
  managed in the `Kingly CSS Color` taxonomy.
* **Background Opacity**: Apply a transparency level (from 100% down to 0%) to
  the background color, without affecting the content.

### Border

* **Border Color, Width, & Style**: Apply a border with a specific color,
  thickness, and style (solid, dashed, dotted).
* **Border Radius**: Round the corners of the layout, with options from `Small`
  to `Full (Pill/Circle)`.

### Alignment

* **Vertical Alignment**: Align the content of all columns vertically (Top,
  Middle, Bottom). Especially useful for columns of different heights.

### Background Media

* **Background Type**: Choose between a solid color, an image, or a video
  background.
* **Media URL & Min Height**: Provide a URL for your media and set a minimum
  height for the section.
* **Image Options**: Control position, repeat, size, and attachment (for
  parallax effects).
* **Video Options**: Control looping, autoplay, and mute settings.
* **Overlay**: Add a color overlay on top of your background media with
  adjustable opacity to improve text readability.

### Shadows & Effects

* **Box Shadow**: Apply a pre-configured drop shadow to the layout container for
  a sense of depth.
* **Filter**: Apply a CSS filter to the layout, such as `Grayscale`, `Blur`,
  or `Sepia`.
* **Opacity**: Adjust the overall transparency of the layout section.
* **Scale**: Scale the size of the layout section.
* **Rotate**: Rotate the layout section.

### Animation

* **Animation Type**: Animate the section as it scrolls into view (
  e.g., `Fade In`, `Slide In`).
* **Transition Options**: Fine-tune the animation's direction, duration, speed,
  and delay.

### Responsiveness

* **Hide on Breakpoint**: Choose to hide the entire layout section on specific
  screen sizes (Mobile, Tablet, or Desktop).
