> ## Documentation Index {#documentation-index}
> Fetch the complete documentation index at: https://docs.typesafe.ai/llms.txt
> Use this file to discover all available pages before exploring further.

# Class: APIPromise<T> {#class-apipromiset}

A promise for the parsed result with access to the HTTP response.

Non-2xx responses reject with an `APIError`, including through `asResponse()`.

## Extends {#extends}

* `Promise`\<`T`>

## Type Parameters {#type-parameters}

### T {#t}

`T`

## Constructors {#constructors}

<a id="sdk-constructor" />

### Constructor {#constructor}

```ts theme={null}
new APIPromise<T>(responsePromise, parseResponse): APIPromise<T>;
```

#### Parameters {#parameters}

##### responsePromise {#responsepromise}

`Promise`\<`Response`>

##### parseResponse {#parseresponse}

(`response`) => `Promise`\<`T`>

#### Returns {#returns}

`APIPromise`\<`T`>

#### Overrides {#overrides}

```ts theme={null}
Promise<T>.constructor
```

## Methods {#methods}

<a id="sdk-asresponse" />

### asResponse() {#asresponse}

```ts theme={null}
asResponse(): Promise<Response>;
```

Resolves to the raw `Response` without parsing the body. SDK requests buffer the full
body under the request timeout before handoff; reading it afterwards is caller-owned.
The caller owns the body; don't also `await` the parsed result on the same promise.

#### Returns {#returns}

`Promise`\<`Response`>

***

<a id="sdk-catch" />

### catch() {#catch}

```ts theme={null}
catch<TResult>(onrejected?): Promise<T | TResult>;
```

Attaches a callback for only the rejection of the Promise.

#### Type Parameters {#type-parameters}

##### TResult {#tresult}

`TResult` = `never`

#### Parameters {#parameters}

##### onrejected? {#onrejected}

((`reason`) => `TResult` | `PromiseLike`\<`TResult`>) | `null`

The callback to execute when the Promise is rejected.

#### Returns {#returns}

`Promise`\<`T` | `TResult`>

A Promise for the completion of the callback.

#### Overrides {#overrides}

```ts theme={null}
Promise.catch
```

***

<a id="sdk-finally" />

### finally() {#finally}

```ts theme={null}
finally(onfinally?): Promise<T>;
```

Attaches a callback that is invoked when the Promise is settled (fulfilled or rejected). The
resolved value cannot be modified from the callback.

#### Parameters {#parameters}

##### onfinally? {#onfinally}

(() => `void`) | `null`

The callback to execute when the Promise is settled (fulfilled or rejected).

#### Returns {#returns}

`Promise`\<`T`>

A Promise for the completion of the callback.

#### Overrides {#overrides}

```ts theme={null}
Promise.finally
```

***

<a id="sdk-map" />

### map() {#map}

```ts theme={null}
map<U>(fn): APIPromise<U>;
```

Transform the parsed result, sharing the HTTP response and a single body parse.

#### Type Parameters {#type-parameters}

##### U {#u}

`U`

#### Parameters {#parameters}

##### fn {#fn}

(`data`) => `U`

#### Returns {#returns}

`APIPromise`\<`U`>

***

<a id="sdk-then" />

### then() {#then}

```ts theme={null}
then<TResult1, TResult2>(onfulfilled?, onrejected?): Promise<TResult1 | TResult2>;
```

Attaches callbacks for the resolution and/or rejection of the Promise.

#### Type Parameters {#type-parameters}

##### TResult1 {#tresult1}

`TResult1` = `T`

##### TResult2 {#tresult2}

`TResult2` = `never`

#### Parameters {#parameters}

##### onfulfilled? {#onfulfilled}

((`value`) => `TResult1` | `PromiseLike`\<`TResult1`>) | `null`

The callback to execute when the Promise is resolved.

##### onrejected? {#onrejected}

((`reason`) => `TResult2` | `PromiseLike`\<`TResult2`>) | `null`

The callback to execute when the Promise is rejected.

#### Returns {#returns}

`Promise`\<`TResult1` | `TResult2`>

A Promise for the completion of which ever callback is executed.

#### Overrides {#overrides}

```ts theme={null}
Promise.then
```

***

<a id="sdk-withresponse" />

### withResponse() {#withresponse}

```ts theme={null}
withResponse(): Promise<WithResponse<T>>;
```

Return the parsed result, HTTP response, and request ID.

#### Returns {#returns}

`Promise`\<[`WithResponse`](/sdk/javascript/api/interfaces/WithResponse)\<`T`>>
