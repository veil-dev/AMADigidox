#[cfg(test)]
mod tests {
    use super::*;
    use soroban_sdk::{Env};

    /// Helper to create a fresh environment and deployed contract client
    fn setup() -> (Env, CounterContractClient<'static>) {
        let env = Env::default();
        let contract_id = env.register_contract(None, CounterContract);
        let client = CounterContractClient::new(&env, &contract_id);
        (env, client)
    }

    #[test]
    fn test_initialize() {
        let (_env, client) = setup();
        client.initialize();
        assert_eq!(client.get_count(), 0);
    }

    #[test]
    fn test_increment_once() {
        let (_env, client) = setup();
        client.initialize();
        let result = client.increment();
        assert_eq!(result, 1);
        assert_eq!(client.get_count(), 1);
    }

    #[test]
    fn test_increment_multiple_times() {
        let (_env, client) = setup();
        client.initialize();
        client.increment();
        client.increment();
        let result = client.increment();
        assert_eq!(result, 3);
        assert_eq!(client.get_count(), 3);
    }

    #[test]
    fn test_decrement() {
        let (_env, client) = setup();
        client.initialize();
        client.increment();
        client.increment();
        let result = client.decrement();
        assert_eq!(result, 1);
        assert_eq!(client.get_count(), 1);
    }

    #[test]
    fn test_decrement_below_zero() {
        let (_env, client) = setup();
        client.initialize();
        let result = client.decrement();
        assert_eq!(result, -1);
        assert_eq!(client.get_count(), -1);
    }

    #[test]
    fn test_reset() {
        let (_env, client) = setup();
        client.initialize();
        client.increment();
        client.increment();
        client.increment();
        client.reset();
        assert_eq!(client.get_count(), 0);
    }

    #[test]
    fn test_get_count_without_initialize_defaults_to_zero() {
        let (_env, client) = setup();
        // get_count before initialize should default to 0 via unwrap_or
        assert_eq!(client.get_count(), 0);
    }

    #[test]
    fn test_full_flow() {
        let (_env, client) = setup();
        client.initialize();

        // Increment to 5
        for _ in 0..5 {
            client.increment();
        }
        assert_eq!(client.get_count(), 5);

        // Decrement to 3
        client.decrement();
        client.decrement();
        assert_eq!(client.get_count(), 3);

        // Reset back to 0
        client.reset();
        assert_eq!(client.get_count(), 0);
    }
}