# Fretboard Diagram

A Joomla 5+ content plugin that converts a custom `{fretboarddiagram}` shortcode into an inline SVG guitar fretboard diagram.

The plugin is designed for displaying guitar scale patterns and/or chord diagrams directly inside Joomla articles without requiring an external image, canvas element, or JavaScript-based drawing library.

## Overview

The plugin recognizes content enclosed by:

```text
{fretboarddiagram}
...
{/fretboardediagram}
```

and replaces it with a generated SVG representation of a six-string guitar fretboard.

For example:

```text
{fretboarddiagram}
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboarddiagram}
```

is converted into an inline SVG fretboard diagram.

The generated diagram is responsive and can scale with its containing element.

---

## Requirements

- Joomla 5+
- PHP version supported by the installed Joomla 5 release
- A standard Joomla template capable of displaying inline SVG
---

# Shortcode Syntax

The basic syntax is:

```text
{fretboarddiagram}
STRING:FRET(SCALE_DEGREE)[FINGER],FRET(SCALE_DEGREE)[FINGER] | ...
{/fretboarddiagram}
```

A complete example:

```text
{fretboarddiagram}
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboarddiagram}
```

The notation is intentionally compact so that scale diagrams can be written directly in Joomla article content.

---

# String Numbers

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

---

# Note Syntax

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

## Fret

The number before the parentheses identifies the fret:

```text
5(1)[1]
^
fret
```

The current parser accepts fret values from `0` through `30`.

Fret `0` can be used if open-string positions are eventually required by the renderer.

## Scale Degree

The number inside parentheses identifies the scale degree:

```text
5(1)[1]
  ^
  scale degree
```

For example:

```text
5(1)[1]
7(2)[3]
9(3)[4]
```

represent scale degrees 1, 2, and 3 respectively.

The parser currently permits scale degrees from `0` through `99`.

The renderer currently uses the scale degree as the visible number displayed inside each note circle.

## Finger

The number inside square brackets identifies the suggested fingering:

```text
5(1)[1]
    ^
    finger
```

For example:

```text
5(1)[1]
7(3)[4]
```

means:

- fret 5, scale degree 1, finger 1
- fret 7, scale degree 3, finger 4

The parser retains the fingering information in the parsed data structure.

The first renderer does **not** currently display the finger number in the circle. It displays the scale degree instead.

The finger information is nevertheless preserved so that a future renderer can display it, use it for accessibility, generate alternate diagram styles, or otherwise make use of the fingering information without changing the shortcode format.

---

# Multiple Notes on a String

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

# Separating Strings

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

---

# Complete Example

The supplied example is:

```text
{fretboarddiagram}
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboardscalediagram}
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

The main parsing method is responsible for converting the string representation into the internal array structure.

Its return type is:

```php
array<int, array<int, array{
    fret:int,
    degree:int,
    finger:int
}>>
```

In practical terms:

```text
string number
    ↓
one or more notes
    ↓
each note contains:
    fret
    degree
    finger
```

The parser:

- removes HTML tags;
- decodes HTML entities;
- separates the individual string definitions using `|`;
- extracts the string number before `:`;
- validates that the string number is between 1 and 6;
- extracts all notes from the string definition;
- validates fret, scale-degree, and finger values;
- stores each valid note in the resulting array.

Invalid string definitions and invalid individual notes are skipped rather than causing the entire diagram to fail.

---

# Validation

The parser performs basic range validation.

## String Number

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

## Fret

The current accepted range is:

```text
0–30
```

## Scale Degree

The current accepted range is:

```text
0–99
```

## Finger

The current accepted range is:

```text
0–9
```

These ranges are deliberately implemented at the parser level so that malformed input does not result in unexpected SVG output.

The limits can be adjusted later if the diagram format needs to support a broader range.


---

# Scale-Degree Display

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

# Root Highlighting

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

---

# Fingering Information

Although the current visual representation uses the scale degree as the note label, fingering remains part of the internal representation.

For example:

```text
8(3)[4]
```

is stored as:

```php
[
    'fret'   => 8,
    'degree' => 3,
    'finger' => 4,
]
```

This is important because the shortcode contains two separate pieces of musical information:

- **what note/scale degree is being played**
- **which finger is recommended**

The current renderer chooses to visualize the first.

A future renderer could instead display:

```text
3
```

with a smaller:

```text
4
```

to indicate the suggested finger, or could provide both pieces of information through SVG metadata or accessibility attributes.

---

# Ordering of Strings

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

---

# Error Handling

Malformed input should not prevent the rest of the Joomla article from rendering.

The parser therefore uses a forgiving approach.

For example, an invalid string number:

```text
7:5(1)[1]
```

is ignored because a standard six-string guitar only has strings 1 through 6.

Likewise, an invalid fret or finger value is skipped.

The intention is that a malformed diagram should fail gracefully rather than generate invalid SVG or interfere with unrelated article content.

---

# Whitespace

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

---

# Delimiters

The notation uses three different delimiters, each with a specific purpose:

| Character | Purpose | Example |
|---|---|---|
| `:` | separates string number from notes | `6:5(1)[1]` |
| `,` | separates notes on the same string | `5(1)[1],8(3)[4]` |
| `\|` | separates strings | `6:... \| 5:...` |
| `()` | contains scale degree | `5(1)[1]` |
| `[]` | contains finger number | `5(1)[1]` |

The syntax is intentionally compact while still keeping each piece of information visually distinguishable.

---

# Current Notation Definition

The currently supported notation can be summarized as:

```text
diagram
    := stringDefinition ("|" stringDefinition)*

stringDefinition
    := stringNumber ":" note ("," note)*

stringNumber
    := 1 | 2 | 3 | 4 | 5 | 6

note
    := fret "(" degree ")" "[" finger "]"

fret
    := integer from 0 through 30

degree
    := integer from 0 through 99

finger
    := integer from 0 through 9
```

This is a conceptual grammar rather than a formal parser specification, but it describes the current input format accurately.

---

# Example Inputs

## Two notes on each string

```text
{fretboarddiagram}
6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboardscalediagram}
```

## Three notes on a string

```text
{fretboarddiagram}
6:5(1)[1],7(2)[2],8(3)[4] | 5:5(4)[1],7(5)[3] | 4:5(7)[1],7(1)[3] | 3:5(3)[1],7(4)[3] | 2:5(5)[1],8(7)[4] | 1:5(1)[1],8(3)[4]
{/fretboardscalediagram}
```

The parser does not require every string to contain the same number of notes.

---

# Development Notes

The plugin is intentionally divided into several conceptual responsibilities:

### 1. Shortcode detection

Find:

```text
{fretboarddiagram}
...
{/fretboardscalediagram}
```

inside Joomla content.

### 2. Input normalization

Decode HTML entities and remove HTML markup from the shortcode contents.

### 3. Parsing

Convert the compact notation into a structured PHP array.

### 4. Validation

Reject values outside the supported ranges.

### 5. Rendering

Convert the structured data into SVG.

### 6. Content replacement

Replace the original shortcode with the generated SVG.

Keeping these responsibilities separate makes the code easier to test and modify.

---

# Internal Data Structure

Each note is represented as:

```php
[
    'fret'   => int,
    'degree' => int,
    'finger' => int,
]
```

Each string contains an array of notes:

```php
[
    6 => [
        [
            'fret'   => 5,
            'degree' => 1,
            'finger' => 1,
        ],
        [
            'fret'   => 8,
            'degree' => 3,
            'finger' => 4,
        ],
    ],
]
```

The complete fretboard is therefore represented as:

```text
fretboard
└── string number
    └── notes
        ├── fret
        ├── degree
        └── finger
```

This structure is intentionally independent of SVG.

The renderer should consume this structure rather than parse the original shortcode directly.

---

# Design Philosophy

The shortcode is intended to be:

- compact enough to type manually;
- readable enough to understand later;
- deterministic;
- easy to parse;
- independent of presentation;
- extensible.

For example:

```text
6:5(1)[1],8(3)[4]
```

can be understood without seeing the resulting diagram:

```text
String 6:
    fret 5  → degree 1 → finger 1
    fret 8  → degree 3 → finger 4
```

This makes the shortcode useful as a textual representation of the scale pattern as well as an instruction to the renderer.
