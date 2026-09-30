> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: NotFoundError {#class-notfounderror}

HTTP 404: the resource was not found.

## Extends {#extends}

* [`APIError`](/sdk/javascript/api/classes/APIError)

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new NotFoundError(
   status, 
   body, 
   headers, 
   message?
): NotFoundError;
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

`NotFoundError`

#### Inherited from {#inherited-from}

[`APIError`](/sdk/javascript/api/classes/APIError).[`constructor`](/sdk/javascript/api/classes/APIError#sdk-constructor)

## Properties {#properties}

<a id="sdk-body" />

### body {#body}

```ts theme={null}
readonly body: unknown;
```

Parsed JSON, response text, or `undefined` for an empty body.

#### Inherited from {#inherited-from}

[`APIError`](/sdk/javascript/api/classes/APIError).[`body`](/sdk/javascript/api/classes/APIError#sdk-body)

***

<a id="sdk-headers" />

### headers {#headers}

```ts theme={null}
readonly headers: Headers;
```

HTTP response headers.

#### Inherited from {#inherited-from}

[`APIError`](/sdk/javascript/api/classes/APIError).[`headers`](/sdk/javascript/api/classes/APIError#sdk-headers)

***

<a id="sdk-requestid" />

### requestId {#requestid}

```ts theme={null}
readonly requestId: string | undefined;
```

Request ID from `x-typesafe-request-id`, or `undefined` when absent.

#### Inherited from {#inherited-from}

[`APIError`](/sdk/javascript/api/classes/APIError).[`requestId`](/sdk/javascript/api/classes/APIError#sdk-requestid)

***

<a id="sdk-status" />

### status {#status}

```ts theme={null}
readonly status: number;
```

HTTP response status code.

#### Inherited from {#inherited-from}

[`APIError`](/sdk/javascript/api/classes/APIError).[`status`](/sdk/javascript/api/classes/APIError#sdk-status)

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

[`APIError`](/sdk/javascript/api/classes/APIError)

#### Inherited from {#inherited-from}

[`APIError`](/sdk/javascript/api/classes/APIError).[`fromResponse`](/sdk/javascript/api/classes/APIError#sdk-fromresponse)
