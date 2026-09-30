> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Interface: ScoreResponse<T> {#interface-scoreresponset}

An expected score with its rubric and probabilities.

## Type Parameters {#type-parameters}

### T {#t}

`T` *extends* [`ScoreCriteria`](/sdk/javascript/api/type-aliases/ScoreCriteria) = [`ScoreCriteria`](/sdk/javascript/api/type-aliases/ScoreCriteria)

## Properties {#properties}

<a id="sdk-confidence" />

### confidence {#confidence}

```ts theme={null}
readonly confidence: number;
```

Reported confidence in the score.

***

<a id="sdk-legend" />

### legend {#legend}

```ts theme={null}
readonly legend: ScoreLegend<T>;
```

Rubric descriptions keyed by score.

***

<a id="sdk-probabilities" />

### probabilities {#probabilities}

```ts theme={null}
readonly probabilities: { readonly [score in number | `${number}`]: number };
```

Probabilities keyed by score.

***

<a id="sdk-score" />

### score {#score}

```ts theme={null}
readonly score: number;
```

Expected score, which may fall between integer rubric levels.

***

<a id="sdk-type" />

### type {#type}

```ts theme={null}
readonly type: "score";
```
