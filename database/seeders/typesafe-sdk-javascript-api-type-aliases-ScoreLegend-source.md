> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Type Alias: ScoreLegend<T> {#type-alias-scorelegendt}

```ts theme={null}
type ScoreLegend<T> = { readonly [score in ScoreOf<T>]: T[score] };
```

Rubric descriptions keyed by score.

## Type Parameters {#type-parameters}

### T {#t}

`T` *extends* [`ScoreCriteria`](/sdk/javascript/api/type-aliases/ScoreCriteria)
