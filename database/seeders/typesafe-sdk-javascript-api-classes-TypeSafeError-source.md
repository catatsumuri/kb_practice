> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: TypeSafeError {#class-typesafeerror}

Base class for SDK errors.

## Extends {#extends}

* `Error`

## Extended by {#extended-by}

* [`APIConnectionError`](/sdk/javascript/api/classes/APIConnectionError)
* [`APIError`](/sdk/javascript/api/classes/APIError)
* [`APIUserAbortError`](/sdk/javascript/api/classes/APIUserAbortError)

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new TypeSafeError(message, options?): TypeSafeError;
```

#### Parameters {#parameters}

##### message {#message}

`string`

##### options? {#options}

`ErrorOptions`

#### Returns {#returns}

`TypeSafeError`

#### Overrides {#overrides}

```ts theme={null}
Error.constructor
```
