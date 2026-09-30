> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Function: noul() {#function-noul}

```ts theme={null}
function noul(instructions?, criteria?): NoulQuestion;
```

Create a yes/no question with optional descriptions for either outcome.

## Parameters {#parameters}

### instructions? {#instructions}

[`EntryType`](/sdk/javascript/api/type-aliases/EntryType) = `null`

The question as text, a JSON object or array; defaults to `null`.

### criteria? {#criteria}

\| \{
`false?`: [`EntryType`](/sdk/javascript/api/type-aliases/EntryType);
`true?`: [`EntryType`](/sdk/javascript/api/type-aliases/EntryType);
}
\| `null`

Optional descriptions of the yes and no outcomes.

#### Type Literal {#type-literal}

\{
`false?`: [`EntryType`](/sdk/javascript/api/type-aliases/EntryType);
`true?`: [`EntryType`](/sdk/javascript/api/type-aliases/EntryType);
}

Optional descriptions of the yes and no outcomes.

##### false? {#false}

[`EntryType`](/sdk/javascript/api/type-aliases/EntryType)

Description of the no outcome.

##### true? {#true}

[`EntryType`](/sdk/javascript/api/type-aliases/EntryType)

Description of the yes outcome.

***

`null`

## Returns {#returns}

[`NoulQuestion`](/sdk/javascript/api/interfaces/NoulQuestion)
