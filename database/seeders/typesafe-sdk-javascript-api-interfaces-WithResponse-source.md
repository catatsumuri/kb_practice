> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Interface: WithResponse<T> {#interface-withresponset}

Parsed data with its HTTP response and request ID.

## Type Parameters {#type-parameters}

### T {#t}

`T`

## Properties {#properties}

<a id="sdk-data" />

### data {#data}

```ts theme={null}
data: T;
```

The parsed response body.

***

<a id="sdk-requestid" />

### requestId {#requestid}

```ts theme={null}
requestId: string | undefined;
```

Request ID from `x-typesafe-request-id`, or `undefined` when absent.

***

<a id="sdk-response" />

### response {#response}

```ts theme={null}
response: Response;
```

The HTTP response, with its body consumed by parsing.
