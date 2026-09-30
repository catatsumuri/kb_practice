> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Interface: SystemOneRequestPayload {#interface-systemonerequestpayload}

Request body for `POST /v1/systemone`, with the model resolved.

## Extends {#extends}

* [`SystemOneRequest`](/sdk/javascript/api/interfaces/SystemOneRequest)

## Properties {#properties}

<a id="sdk-model" />

### model {#model}

```ts theme={null}
model: string;
```

Model override; omitted values inherit `defaultModel`.

#### Overrides {#overrides}

[`SystemOneRequest`](/sdk/javascript/api/interfaces/SystemOneRequest).[`model`](/sdk/javascript/api/interfaces/SystemOneRequest#sdk-model)

***

<a id="sdk-questions" />

### questions {#questions}

```ts theme={null}
questions: Questions;
```

Nonempty questions keyed by the names used to identify their answers.

#### Inherited from {#inherited-from}

[`SystemOneRequest`](/sdk/javascript/api/interfaces/SystemOneRequest).[`questions`](/sdk/javascript/api/interfaces/SystemOneRequest#sdk-questions)

***

<a id="sdk-state" />

### state {#state}

```ts theme={null}
state: EntryType;
```

Text, a JSON object or array, or `null` to evaluate.

#### Inherited from {#inherited-from}

[`SystemOneRequest`](/sdk/javascript/api/interfaces/SystemOneRequest).[`state`](/sdk/javascript/api/interfaces/SystemOneRequest#sdk-state)
