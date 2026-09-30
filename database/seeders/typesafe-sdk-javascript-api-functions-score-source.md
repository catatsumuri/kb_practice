> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Function: score() {#function-score}

```ts theme={null}
function score<T>(instructions, criteria): ScoreQuestion<T>;
```

Create a score question using an ordered rubric.

## Type Parameters {#type-parameters}

### T {#t}

`T` *extends* [`ScoreCriteria`](/sdk/javascript/api/type-aliases/ScoreCriteria)

## Parameters {#parameters}

### instructions {#instructions}

[`EntryType`](/sdk/javascript/api/type-aliases/EntryType)

The question as text, a JSON object or array, or `null`.

### criteria {#criteria}

`T`

At least two descriptions indexed by score from zero; entries may be `null`.

## Returns {#returns}

[`ScoreQuestion`](/sdk/javascript/api/interfaces/ScoreQuestion)\<`T`>
