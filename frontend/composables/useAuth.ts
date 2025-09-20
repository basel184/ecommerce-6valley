type User = any

export function useAuth() {
  const token = useState<string | null>('auth_token', () => null)
  const user = useState<User | null>('auth_user', () => null)

  function setToken(t: string | null) {
    token.value = t
  }
  function setUser(u: User | null) {
    user.value = u
  }

  return { token, user, setToken, setUser }
}
