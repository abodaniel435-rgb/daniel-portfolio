# E-R Diagram (Textual Representation)

## Entities
- **User** (id, email, password_hash, first_name, last_name, phone, address, is_admin, is_active, timestamps)
- **Room** (id, name, room_type, description, price_per_night, max_occupancy, amenities, image_filename, is_available, is_featured, timestamps)
- **Booking** (id, user_id FK, room_id FK, check_in, check_out, num_guests, total_price, status, special_requests, admin_notes, timestamps)
- **Feedback** (id, user_id FK nullable, name, email, rating, message, is_approved, created_at)
- **ContactMessage** (id, name, email, subject, message, is_read, created_at)
- **SliderImage** (id, title, caption, image_filename, link_url, sort_order, is_active, created_at)
- **PasswordResetToken** (id, user_id FK, token, expires_at, used, created_at)

## Relationships
- User 1 — * Booking
- Room 1 — * Booking
- User 1 — * Feedback
- User 1 — * PasswordResetToken

## Booking status values
pending | approved | disapproved | cancelled
