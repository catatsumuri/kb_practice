> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: APIUserAbortError {#class-apiuseraborterror}

The caller cancelled the request through an `AbortSignal`.

## Extends {#extends}

* [`TypeSafeError`](/sdk/javascript/api/classes/TypeSafeError)

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new APIUserAbortError(message?, options?): APIUserAbortError;
```

#### Parameters {#parameters}

##### message? {#message}

`string` = `"Request was aborted."`

##### options? {#options}

`ErrorOptions`

#### Returns {#returns}

`APIUserAbortError`

#### Overrides {#overrides}

[`TypeSafeError`](/sdk/javascript/api/classes/TypeSafeError).[`constructor`](/sdk/javascript/api/classes/TypeSafeError#sdk-constructor)
