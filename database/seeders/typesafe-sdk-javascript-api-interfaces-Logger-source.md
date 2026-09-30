> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Interface: Logger {#interface-logger}

Log methods accepting a message and structured values; compatible with `console`.

## Methods {#methods}

<a id="sdk-debug" />

### debug() {#debug}

```ts theme={null}
debug(message, ...args): void;
```

#### Parameters {#parameters}

##### message {#message}

`string`

##### args {#args}

...`unknown`\[]

#### Returns {#returns}

`void`

***

<a id="sdk-error" />

### error() {#error}

```ts theme={null}
error(message, ...args): void;
```

#### Parameters {#parameters}

##### message {#message}

`string`

##### args {#args}

...`unknown`\[]

#### Returns {#returns}

`void`

***

<a id="sdk-info" />

### info() {#info}

```ts theme={null}
info(message, ...args): void;
```

#### Parameters {#parameters}

##### message {#message}

`string`

##### args {#args}

...`unknown`\[]

#### Returns {#returns}

`void`

***

<a id="sdk-warn" />

### warn() {#warn}

```ts theme={null}
warn(message, ...args): void;
```

#### Parameters {#parameters}

##### message {#message}

`string`

##### args {#args}

...`unknown`\[]

#### Returns {#returns}

`void`
