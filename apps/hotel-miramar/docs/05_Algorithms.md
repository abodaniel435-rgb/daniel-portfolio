# Algorithms

## Booking Creation
1. Validate check-out > check-in and check-in >= today
2. Validate num_guests <= room.max_occupancy
3. Query approved bookings for same room with overlapping date range
4. If conflict → reject
5. total_price = price_per_night × nights
6. Insert booking with status = 'pending'

## Booking Approval (Admin)
1. Re-query for conflicting approved bookings (excluding current)
2. Check room.is_available
3. If clear → set status = 'approved'; else reject with message

## Availability Search
1. Filter rooms by is_available, max_occupancy, optional room_type
2. Exclude rooms that have any approved booking overlapping requested dates
3. Return remaining rooms ordered by price
