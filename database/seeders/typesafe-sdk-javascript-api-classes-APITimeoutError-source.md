> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: APITimeoutError {#class-apitimeouterror}

The full response did not arrive within the timeout. A kind of `APIConnectionError`.

## Extends {#extends}

* [`APIConnectionError`](/sdk/javascript/api/classes/APIConnectionError)

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new APITimeoutError(timeoutMs, options?): APITimeoutError;
```

#### Parameters {#parameters}

##### timeoutMs {#timeoutms}

`number`

##### options? {#options}

`ErrorOptions`

#### Returns {#returns}

`APITimeoutError`

#### Overrides {#overrides}

[`APIConnectionError`](/sdk/javascript/api/classes/APIConnectionError).[`constructor`](/sdk/javascript/api/classes/APIConnectionError#sdk-constructor)

## Properties {#properties}

<a id="sdk-timeoutms" />

### timeoutMs {#timeoutms}

```ts theme={null}
readonly timeoutMs: number;
```

Configured timeout in milliseconds.
