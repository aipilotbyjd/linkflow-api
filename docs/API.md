# LinkFlow API Documentation

**Base URL:** `https://linkflow-api.test/api/v1`

**Authentication:** Bearer Token (Passport)

---

## Authentication

### Register

Create a new user account.

**Endpoint:** `POST /register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Success Response (201):**
```json
{
    "message": "User registered successfully.",
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-30T10:00:00.000000Z",
        "updated_at": "2026-01-30T10:00:00.000000Z"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer"
}
```

**Error Response (422):**
```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["This email is already registered."]
    }
}
```

---

### Login

Authenticate user and get access token.

**Endpoint:** `POST /login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (200):**
```json
{
    "message": "Login successful.",
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-30T10:00:00.000000Z",
        "updated_at": "2026-01-30T10:00:00.000000Z"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer"
}
```

**Error Response (401):**
```json
{
    "message": "Invalid credentials."
}
```

---

### Logout

Revoke current access token.

**Endpoint:** `POST /logout`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
    "message": "Logged out successfully."
}
```

**Error Response (401):**
```json
{
    "message": "Unauthenticated."
}
```

---

## User

### Get Profile

Get authenticated user's profile.

**Endpoint:** `GET /user`

**Headers:**
```
Accept: application/json
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-30T10:00:00.000000Z",
        "updated_at": "2026-01-30T10:00:00.000000Z"
    }
}
```

---

### Update Profile

Update authenticated user's profile.

**Endpoint:** `PUT /user`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Updated",
    "email": "john.updated@example.com"
}
```

> Note: All fields are optional. Only send fields you want to update.

**Success Response (200):**
```json
{
    "message": "Profile updated successfully.",
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Updated",
        "email": "john.updated@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-30T10:00:00.000000Z",
        "updated_at": "2026-01-30T10:30:00.000000Z"
    }
}
```

**Error Response (422):**
```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["This email is already taken."]
    }
}
```

---

### Change Password

Change authenticated user's password.

**Endpoint:** `PUT /user/password`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
    "current_password": "password123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Success Response (200):**
```json
{
    "message": "Password changed successfully."
}
```

**Error Response (422):**
```json
{
    "message": "The current password is incorrect.",
    "errors": {
        "current_password": ["Current password is incorrect."]
    }
}
```

---

### Delete Account

Delete authenticated user's account.

**Endpoint:** `DELETE /user`

**Headers:**
```
Accept: application/json
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
    "message": "Account deleted successfully."
}
```

---

## Error Responses

### Unauthenticated (401)

Returned when accessing protected routes without valid token.

```json
{
    "message": "Unauthenticated."
}
```

### Validation Error (422)

Returned when request validation fails.

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message for this field."]
    }
}
```

### Not Found (404)

Returned when resource is not found.

```json
{
    "message": "Not Found."
}
```

### Server Error (500)

Returned when an unexpected error occurs.

```json
{
    "message": "Server Error."
}
```

---

## Using the API

### JavaScript (Fetch)

```javascript
// Login
const response = await fetch('https://linkflow-api.test/api/v1/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        email: 'john@example.com',
        password: 'password123',
    }),
});

const data = await response.json();
const token = data.access_token;

// Authenticated request
const userResponse = await fetch('https://linkflow-api.test/api/v1/user', {
    headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
    },
});
```

### cURL

```bash
# Login
curl -X POST https://linkflow-api.test/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Get Profile (with token)
curl -X GET https://linkflow-api.test/api/v1/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {access_token}"
```
