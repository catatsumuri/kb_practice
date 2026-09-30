> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Variable: ENV {#variable-env}

```ts theme={null}
const ENV: object;
```

Environment variable names for client configuration. Explicit options take precedence.

## Type Declaration {#type-declaration}

<a id="sdk-apikey" />

### apiKey {#apikey}

```ts theme={null}
readonly apiKey: "TYPESAFE_API_KEY" = "TYPESAFE_API_KEY";
```

Required API key; used when `apiKey` is omitted.

<a id="sdk-baseurl" />

### baseURL {#baseurl}

```ts theme={null}
readonly baseURL: "TYPESAFE_BASE_URL" = "TYPESAFE_BASE_URL";
```

API root; defaults to `https://api.typesafe.ai`.

<a id="sdk-defaultmodel" />

### defaultModel {#defaultmodel}

```ts theme={null}
readonly defaultModel: "TYPESAFE_DEFAULT_MODEL" = "TYPESAFE_DEFAULT_MODEL";
```

Default model name; defaults to `jev-latest`.

<a id="sdk-loglevel" />

### logLevel {#loglevel}

```ts theme={null}
readonly logLevel: "TYPESAFE_LOG_LEVEL" = "TYPESAFE_LOG_LEVEL";
```

Log level; defaults to `warn`.
