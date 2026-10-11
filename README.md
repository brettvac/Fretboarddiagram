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

### Fretboard Diagram Delimiters

The fretboard diagram notation uses three different delimiters, each with a specific purpose:

| Character | Purpose | Example |
|---|---|---|
| `:` | separates string number from notes | `6:5(1)[1]` |
| `,` | separates notes on the same string | `5(1)[1],8(3)[4]` |
| `\|` | separates strings | `6:... \| 5:...` |
| `()` | contains the scale degree | `5(1)[1]` |
| `[]` | contains the finger number | `5(1)[1]` |

### Chord diagram delimiters

Chord diagram notation uses delimiters to identify the chord name, starting fret, and fingering for each of the six strings.

| Character | Purpose | Example |
|---|---|---|
| `:` | Separates the chord name from the string positions | `Ab:...` |
| `()` | Encloses the six string positions when a starting fret is specified | `Ab:4(...)` |
| `;` | Separates the six string positions, ordered from the sixth string to the first string | `4[1];6[3];6[4];5[2];4[1];4[1]` |
| `x` | Indicates a muted string | `x;x;1[1];1[1];1[1];2[2]` |
| `o` | Indicates an open string | `x;x;o;1[1];o;1[2]` |
| `[]` | Contains the finger number | `4[1]` |

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

The guitar strings are identified by their standard string numbers. String `6` is the low E string and string `1` is the high E string. Only string numbers from `1` through `6` are accepted. Invalid string numbers are ignored by the parser.

#### Ordering of Strings

Although the input may be written in any order, string 6 is rendered at the bottom of the fretboard and string 1 at the top.

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

### Frets

The number before the parentheses identifies the fret:

```text
5(1)[1]
^
fret
```

The current parser accepts fret values from `0` through `24`. Use fret `0` to represent an open-string position.

```text
1:0(1)[0]
```

This represents an open first string, with scale degree 1 and finger 0.

### Scale Degrees

The number inside parentheses identifies the scale degree:

```text
5(1)[1]
  ^
  scale degree
```

For example:

```text
9(3)[4]
```

represents scale degree 3.

The parser currently permits scale degrees from `1` through `99`. 

#### Root Highlighting

Scale degree `1` represents the root or tonic of the scale. The root note (scale degree 1) is highlighted in a different colour to make it easy to identify on the fretboard.

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

### Finger information

The renderer currently uses the fingering as the visible number displayed inside each note circle.

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

The parser accepts finger values from 0 through 5, as well as T to represent the thumb.

The standard finger numbers indicate the fretting-hand fingers:

1 — Index finger

2 — Middle finger

3 — Ring finger

4 — Little finger

0 — Open string or no fretting-hand finger assigned

T — Thumb

The finger number is displayed inside the note circle. The note's colour is determined by its scale degree, not its fingering. Scale degree 1 is highlighted in a different colour to distinguish the root note from the other notes.

Important: The current parser requires the finger information in square brackets. A note without this information will not be recognized.

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

### Example Inputs

A complete six-string diagram therefore consists of six string definitions separated by pipes.

#### Two notes on each string

```text
{fretboarddiagram}
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboardscalediagram}
```

#### Three notes on a string

```text
{fretboarddiagram}
6:5(1)[1],7(2)[2],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboardscalediagram}
```

## Chord Diagrams

A chord diagram starts with the chord name, followed by a colon and six string positions separated by semicolons.

### String positions

The six positions are always listed in this order:

1. Sixth string (low E)
2. Fifth string (A)
3. Fourth string (D)
4. Third string (G)
5. Second string (B)
6. First string (high E)

Each string position can be:

- `x`
- `o`
- `0`
- `fret`
- `fret[finger]`

`x` indicates a muted string. `o` and `0` indicate an open string. 

A fret number indicates a fretted note, and a fret number followed by `[finger]` specifies the fretting-hand finger.

### Open-position chords

For a chord in the open position:

```text
{fretboarddiagram}B♭5:6[1];8[3];8[4];x;x;x{/fretboarddiagram}
```

The six positions correspond to the six guitar strings, from the sixth string through the first string.

## Movable chords

For movable chord shapes, the starting fret is placed immediately before the parentheses:

```text
{fretboarddiagram}E♭5:11(11[1];13[3];13[4];x;x;x){/fretboarddiagram}
```

Here, `11` before the parentheses indicates that the diagram starts at the 11th fret. The six string positions inside the parentheses then use absolute fret numbers: 11, 13, 13, muted, muted, muted.

The chord name can contain any text before the colon. The parser does not validate the chord name itself.

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

Whitespace around the pipe is optional.

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
