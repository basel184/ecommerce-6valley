// Manage guest_id required by Laravel's apiGuestCheck middleware
export function useGuest() {
  const guestId = useState<number | null>('guest_id', () => null)
  const setGuestId = (id: number | null) => (guestId.value = id)
  return { guestId, setGuestId }
}
