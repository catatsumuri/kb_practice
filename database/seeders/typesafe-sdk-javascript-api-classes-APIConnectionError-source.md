> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: APIConnectionError {#class-apiconnectionerror}

The request or response-body delivery failed (DNS, TLS, connection closed, etc.).

## Extends {#extends}

* [`TypeSafeError`](/sdk/javascript/api/classes/TypeSafeError)

## Extended by {#extended-by}

* [`APITimeoutError`](/sdk/javascript/api/classes/APITimeoutError)

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new APIConnectionError(message?, options?): APIConnectionError;
```

#### Parameters {#parameters}

##### message? {#message}

`string` = `"Connection error."`

##### options? {#options}

`ErrorOptions`

#### Returns {#returns}

`APIConnectionError`

#### Overrides {#overrides}

[`TypeSafeError`](/sdk/javascript/api/classes/TypeSafeError).[`constructor`](/sdk/javascript/api/classes/TypeSafeError#sdk-constructor)
