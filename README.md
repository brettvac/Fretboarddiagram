# Fretboard Diagram

Fretboard Diagram is a Joomla 5+ content plugin that converts a `{fretboarddiagram}` shortcode into an inline SVG guitar fretboard diagram.

Use this plugin to easily display guitar scale patterns and/or chord diagrams directly inside Joomla articles without requiring an external image, canvas element, or JavaScript-based drawing library.

## Overview

The plugin recognizes content enclosed by:

```text
{fretboarddiagram}
...
{/fretboardediagram}
```

and replaces it with a generated SVG representation of a six-string guitar fretboard.

## Fretboard Scale Diagram Example

```text
{fretboarddiagram}
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboarddiagram}
```

It describes the following notes:

| String | Notes |
|---|---|
| 6 | fret 5, degree 1, finger 1; fret 8, degree 3, finger 4 |
| 5 | fret 5, degree 4, finger 1; fret 7, degree 5, finger 3 |
| 4 | fret 5, degree 7, finger 1; fret 7, degree 1, finger 3 |
| 3 | fret 5, degree 3, finger 1; fret 7, degree 4, finger 3 |
| 2 | fret 5, degree 5, finger 1; fret 8, degree 7, finger 4 |
| 1 | fret 5, degree 1, finger 1; fret 8, degree 3, finger 4 |

The root/tonic is represented by scale degree `1`.

The renderer highlights degree `1` to make the root notes immediately distinguishable from the other scale degrees.

### String Numbers

The guitar strings are identified by their standard string numbers:

```text
6:
5:
4:
3:
2:
1:
```

String `6` is the low E string and string `1` is the high E string.

The renderer displays the strings in the conventional guitar-diagram orientation:

```text
6  ← top
5
4
3
2
1  ← bottom
```

Therefore, although the input may be written in any order, string 6 is rendered at the top of the fretboard and string 1 at the bottom.

Only string numbers from `1` through `6` are accepted.

Invalid string numbers are ignored by the parser.

### Note Syntax

Each note on a string has three components:

```text
FRET(SCALE_DEGREE)[FINGER]
```

For example:

```text
5(1)[1]
```

means:

- `5` — fret 5
- `(1)` — scale degree 1
- `[1]` — finger 1

Another example:

```text
8(3)[4]
```

means:

- `8` — fret 8
- `(3)` — scale degree 3
- `[4]` — finger 4

The three values have different purposes and should not be confused.

### Frets

The number before the parentheses identifies the fret:

```text
5(1)[1]
^
fret
```

The current parser accepts fret values from `0` through `30`.

Fret `0` can be used if open-string positions are eventually required by the renderer.

### Scale Degrees

The number inside parentheses identifies the scale degree:

```text
5(1)[1]
  ^
  scale degree
```

For example:

```text
5(1)[1]
```

represents scale degree 1.

```text
7(2)[3]
```

represents scale degree 2.

```text
9(3)[4]
```

represents scale degree 3.

The parser currently permits scale degrees from `0` through `99`.

The renderer currently uses the scale degree as the visible number displayed inside each note circle.

### Finger information

The number inside square brackets identifies the suggested fingering:

```text
5(1)[1]
     ^
     finger
```

For example:

```text
5(1)[1]
```

means:

- fret 5, scale degree 1, finger 1

```text
7(3)[4]
```

means:

- fret 7, scale degree 3, finger 4

The parser retains the fingering information in the parsed data structure.

The first renderer does **not** currently display the finger number in the circle. It displays the scale degree instead.

The finger information is nevertheless preserved so that a future renderer can display it, use it for accessibility, generate alternate diagram styles, or otherwise make use of the fingering information without changing the shortcode format.

### Multiple Notes on a String

Multiple notes on the same string are separated with commas.

For example:

```text
6:5(1)[1],8(3)[4]
```

means that string 6 contains two notes:

```text
5(1)[1]
8(3)[4]
```

The comma is therefore a **note separator**.

Additional notes can be added in the same way:

```text
6:5(1)[1],7(2)[2],8(3)[4],10(5)[4]
```

---

### Separating Strings

Individual strings are separated with a pipe character:

```text
|
```

For example:

```text
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3]
```

contains two string definitions:

```text
6:5(1)[1],8(3)[4]
```

and:

```text
5:5(4)[1],7(5)[3]
```

A complete six-string diagram therefore consists of six string definitions separated by pipes.

Whitespace around the pipe is optional.

Both of these forms are equivalent:

```text
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3]
```

and:

```text
6:5(1)[1],8(3)[4]|5:5(4)[1],7(5)[3]
```

### Validation

The parser performs basic range validation.

### String Number

Valid:

```text
1
2
3
4
5
6
```

Invalid values are ignored.

#### Fret

The current accepted range is:

```text
0–30
```

#### Scale Degree

The current accepted range is:

```text
0–99
```

#### Finger

The current accepted range is:

```text
0–9
```

These ranges are deliberately implemented at the parser level so that malformed input does not result in unexpected SVG output.

#### Scale-Degree Display

The first renderer displays the **scale degree** inside each note circle.

For example:

```text
5(1)[1]
```

produces a note circle containing:

```text
1
```

rather than:

```text
1
```

because the displayed value is the scale degree.

The finger number `[1]` is retained in the parsed data but is not the primary visual label in this renderer.

This distinction is intentional:

```text
5(1)[1]
│ │  │
│ │  └── fingering information
│ └────── scale degree displayed in the circle
└──────── fret position
```

---

#### Root Highlighting

Scale degree `1` represents the root or tonic of the scale.

The renderer gives degree `1` a distinct visual treatment so that root notes can be identified immediately.

For example:

```text
5(1)[1]
```

and:

```text
7(3)[4]
```

are both rendered as note positions, but the degree `1` note receives the root highlighting.

The root is determined from the scale-degree value rather than from the fret or finger number.

This means that any note encoded as:

```text
(followed by degree 1)
```

is treated as a root note.

## Ordering of Strings

The parser stores strings using their numerical identifiers:

```php
$parsedFretboard[$stringNum] = [];
```

The resulting array is sorted by string number before it is returned.

This ensures predictable ordering regardless of the order in which strings were supplied in the shortcode.

For example, input such as:

```text
1:5(1)[1] | 6:5(1)[1] | 3:5(3)[2]
```

can still be normalized into numerical string order.

The renderer is responsible for mapping those string numbers to their visual positions, with string 6 at the top and string 1 at the bottom.

### Delimiters

The notation uses three different delimiters, each with a specific purpose:

| Character | Purpose | Example |
|---|---|---|
| `:` | separates string number from notes | `6:5(1)[1]` |
| `,` | separates notes on the same string | `5(1)[1],8(3)[4]` |
| `\|` | separates strings | `6:... \| 5:...` |
| `()` | contains scale degree | `5(1)[1]` |
| `[]` | contains finger number | `5(1)[1]` |

The syntax is intentionally compact while still keeping each piece of information visually distinguishable.

## Chord Diagrams

A chord diagram starts with the chord name, followed by a colon and six string positions separated by semicolons.

For a chord in the open position:

```text
{fretboarddiagram}B♭5:6[1];8[3];8[4];x;x;x{/fretboarddiagram}
```

The six positions correspond to the six guitar strings, from the sixth string through the first string.

Each string position can be:

- `x`
- `o`
- `0`
- `fret`
- `fret[finger]`

`x` indicates a muted string. `o` and `0` indicate an open string. A fret number indicates a fretted note, and a fret number followed by `[finger]` specifies the fretting-hand finger.

For movable chord shapes, the starting fret is placed immediately before the parentheses:

```text
{fretboarddiagram}E♭5:11(11[1];13[3];13[4];x;x;x){/fretboarddiagram}
```

Here, `11` before the parentheses indicates that the diagram starts at the 11th fret. The six string positions inside the parentheses then use absolute fret numbers: 11, 13, 13, muted, muted, muted.

The chord name can contain any text before the colon. The parser does not validate the chord name itself.

## Example Inputs

### Two notes on each string

```text
{fretboarddiagram}
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboardscalediagram}
```

### Three notes on a string

```text
{fretboarddiagram}
6:5(1)[1],7(2)[2],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboardscalediagram}
```

## Error Handling

Malformed input should not prevent the rest of the Joomla article from rendering.

The parser therefore uses a forgiving approach.

For example, an invalid string number:

```text
7:5(1)[1]
```

is ignored because a standard six-string guitar only has strings 1 through 6.

Likewise, an invalid fret or finger value is skipped.

The intention is that a malformed diagram should fail gracefully rather than generate invalid SVG or interfere with unrelated article content.

### Whitespace

Whitespace around the syntax is not significant.

For example:

```text
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3]
```

and:

```text
6:5(1)[1],8(3)[4]|5:5(4)[1],7(5)[3]
```

are equivalent.

Whitespace surrounding the entire shortcode content is also removed before parsing.

## Requirements

This plugin requires Joomla 5+ and PHP versions 8.1+.
