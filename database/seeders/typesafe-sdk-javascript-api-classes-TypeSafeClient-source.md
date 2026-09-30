> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: TypeSafeClient {#class-typesafeclient}

Client for the TypeSafe AI API.

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new TypeSafeClient(config?): TypeSafeClient;
```

Create a client for the TypeSafe AI API.

Explicit options take precedence over environment variables, then SDK defaults.
Empty or whitespace-only environment values are ignored.

#### Parameters {#parameters}

##### config? {#config}

[`TypeSafeClientConfig`](/sdk/javascript/api/interfaces/TypeSafeClientConfig) = `{}`

#### Returns {#returns}

`TypeSafeClient`

#### Throws {#throws}

The API key is missing, configuration is invalid, or the runtime is unsupported.

## Properties {#properties}

<a id="sdk-baseurl" />

### baseURL {#baseurl}

```ts theme={null}
readonly baseURL: string;
```

API root with trailing slashes removed.

***

<a id="sdk-defaultheaders" />

### defaultHeaders {#defaultheaders}

```ts theme={null}
readonly defaultHeaders: Readonly<Record<string, string>>;
```

Additional headers sent with each request.

***

<a id="sdk-defaultmodel" />

### defaultModel {#defaultmodel}

```ts theme={null}
readonly defaultModel: string;
```

Model used when a request omits `model`.

***

<a id="sdk-fetch" />

### fetch {#fetch}

```ts theme={null}
readonly fetch: Fetch;
```

HTTP fetch implementation.

***

<a id="sdk-logger" />

### logger {#logger}

```ts theme={null}
readonly logger: Logger;
```

The configured logger, filtered to `logLevel`.

***

<a id="sdk-loglevel" />

### logLevel {#loglevel}

```ts theme={null}
readonly logLevel: LogLevel;
```

Configured log verbosity.

***

<a id="sdk-models" />

### models {#models}

```ts theme={null}
readonly models: Models;
```

The models available to the account.

***

<a id="sdk-retry" />

### retry {#retry}

```ts theme={null}
readonly retry: RetryPolicy;
```

Retry settings with constructor overrides applied.

***

<a id="sdk-timeout" />

### timeout {#timeout}

```ts theme={null}
readonly timeout: number;
```

Timeout per attempt in milliseconds.

## Methods {#methods}

<a id="sdk-systemone" />

### systemOne() {#systemone}

```ts theme={null}
systemOne<Q>(request, options?): APIPromise<SystemOneResult<Q>>;
```

Answer named questions about text or structured state.

#### Type Parameters {#type-parameters}

##### Q {#q}

`Q` *extends* [`Questions`](/sdk/javascript/api/interfaces/Questions)

#### Parameters {#parameters}

##### request {#request}

[`SystemOneRequest`](/sdk/javascript/api/interfaces/SystemOneRequest)\<`Q`>

State, questions, and an optional model override.

##### options? {#options}

[`RequestOptions`](/sdk/javascript/api/interfaces/RequestOptions) = `{}`

Per-call timeout, retry, headers, and cancellation settings.

#### Returns {#returns}

[`APIPromise`](/sdk/javascript/api/classes/APIPromise)\<[`SystemOneResult`](/sdk/javascript/api/interfaces/SystemOneResult)\<`Q`>>

Answers typed by question name and criteria, with model and token usage.

#### Throws {#throws}

Questions are empty, or score criteria are not a list of at least two entries.

#### Throws {#throws}

The server returns a non-2xx response after retries.

#### Throws {#throws}

The request cannot connect or times out after retries.

#### Throws {#throws}

The caller aborts the request.

#### Example {#example}

```ts theme={null}
const { answers } = await client.systemOne({
  state: "I was charged twice. Please help.",
  questions: { billing: noul("Is this about billing?") },
});
console.log(answers.billing.noul);
```
