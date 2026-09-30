> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: APIError {#class-apierror}

An unsuccessful HTTP response from the API.

## Extends {#extends}

* [`TypeSafeError`](/sdk/javascript/api/classes/TypeSafeError)

## Extended by {#extended-by}

* [`AuthenticationError`](/sdk/javascript/api/classes/AuthenticationError)
* [`BadRequestError`](/sdk/javascript/api/classes/BadRequestError)
* [`InternalServerError`](/sdk/javascript/api/classes/InternalServerError)
* [`NotFoundError`](/sdk/javascript/api/classes/NotFoundError)
* [`PermissionDeniedError`](/sdk/javascript/api/classes/PermissionDeniedError)
* [`RateLimitError`](/sdk/javascript/api/classes/RateLimitError)
* [`UnprocessableEntityError`](/sdk/javascript/api/classes/UnprocessableEntityError)

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new APIError(
   status, 
   body, 
   headers, 
   message?
): APIError;
```

#### Parameters {#parameters}

##### status {#status}

`number`

##### body {#body}

`unknown`

##### headers {#headers}

`Headers`

##### message? {#message}

`string`

#### Returns {#returns}

`APIError`

#### Overrides {#overrides}

[`TypeSafeError`](/sdk/javascript/api/classes/TypeSafeError).[`constructor`](/sdk/javascript/api/classes/TypeSafeError#sdk-constructor)

## Properties {#properties}

<a id="sdk-body" />

### body {#body}

```ts theme={null}
readonly body: unknown;
```

Parsed JSON, response text, or `undefined` for an empty body.

***

<a id="sdk-headers" />

### headers {#headers}

```ts theme={null}
readonly headers: Headers;
```

HTTP response headers.

***

<a id="sdk-requestid" />

### requestId {#requestid}

```ts theme={null}
readonly requestId: string | undefined;
```

Request ID from `x-typesafe-request-id`, or `undefined` when absent.

***

<a id="sdk-status" />

### status {#status}

```ts theme={null}
readonly status: number;
```

HTTP response status code.

## Methods {#methods}

<a id="sdk-fromresponse" />

### fromResponse() {#fromresponse}

```ts theme={null}
static fromResponse(
   status, 
   body, 
   headers
): APIError;
```

Create the error subclass for an HTTP status code.

#### Parameters {#parameters}

##### status {#status}

`number`

##### body {#body}

`unknown`

##### headers {#headers}

`Headers`

#### Returns {#returns}

`APIError`
