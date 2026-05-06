# API Response Standard

## Success (with data)
{
  "message": "Action completed successfully",
  "data": {}
}

## Success (no data)
{
  "message": "Action completed successfully"
}

## Validation Error
{
  "error": "Validation failed",
  "fields": {
    "field_name": ["Error message"]
  }
}

## Generic Error
{
  "error": "Something went wrong"
}

## Rules
- All success responses must use ApiResponse::success()
- Services must NOT return error arrays
- Services must throw ValidationException for business errors
- Global handler formats all errors