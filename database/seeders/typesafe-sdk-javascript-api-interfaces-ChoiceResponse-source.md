> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Interface: ChoiceResponse<T> {#interface-choiceresponset}

A selected label and its probabilities.

## Type Parameters {#type-parameters}

### T {#t}

`T` *extends* [`ChoiceCriteria`](/sdk/javascript/api/type-aliases/ChoiceCriteria) = [`ChoiceCriteria`](/sdk/javascript/api/type-aliases/ChoiceCriteria)

## Properties {#properties}

<a id="sdk-choice" />

### choice {#choice}

```ts theme={null}
readonly choice: keyof T & string;
```

The selected label.

***

<a id="sdk-confidence" />

### confidence {#confidence}

```ts theme={null}
readonly confidence: number;
```

Reported confidence in the selected label.

***

<a id="sdk-probabilities" />

### probabilities {#probabilities}

```ts theme={null}
readonly probabilities: { readonly [label in string | number | symbol]: number };
```

Probabilities keyed by label.

***

<a id="sdk-type" />

### type {#type}

```ts theme={null}
readonly type: "choice";
```
