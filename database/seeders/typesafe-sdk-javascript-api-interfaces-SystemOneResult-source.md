> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Interface: SystemOneResult<Q> {#interface-systemoneresultq}

Answers keyed by question name, with model and usage metadata.

## Type Parameters {#type-parameters}

### Q {#q}

`Q` *extends* [`Questions`](/sdk/javascript/api/interfaces/Questions)

## Properties {#properties}

<a id="sdk-answers" />

### answers {#answers}

```ts theme={null}
readonly answers: { readonly [K in string | number | symbol]: ResultFor<Q[K]> };
```

Answers with types inferred from the supplied questions.

***

<a id="sdk-model" />

### model {#model}

```ts theme={null}
readonly model: string;
```

The model used to answer the request.

***

<a id="sdk-usage" />

### usage {#usage}

```ts theme={null}
readonly usage: Usage;
```

Token usage for the request.
