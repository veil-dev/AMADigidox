#![no_std]
use soroban_sdk::{contract, contractimpl, contracttype, Env, Symbol, symbol_short, log};

#[contracttype]
pub enum DataKey {
    Counter,
}

#[contract]
pub struct CounterContract;

#[contractimpl]
impl CounterContract {
    /// Initialize the counter to zero
    pub fn initialize(env: Env) {
        env.storage().instance().set(&DataKey::Counter, &0_i64);
        log!(&env, "Counter initialized to 0");
    }

    /// Increment the counter by 1 and return the new value
    pub fn increment(env: Env) -> i64 {
        let mut count: i64 = env
            .storage()
            .instance()
            .get(&DataKey::Counter)
            .unwrap_or(0);
        count += 1;
        env.storage().instance().set(&DataKey::Counter, &count);
        log!(&env, "Counter incremented to: {}", count);
        count
    }

    /// Decrement the counter by 1 and return the new value
    pub fn decrement(env: Env) -> i64 {
        let mut count: i64 = env
            .storage()
            .instance()
            .get(&DataKey::Counter)
            .unwrap_or(0);
        count -= 1;
        env.storage().instance().set(&DataKey::Counter, &count);
        log!(&env, "Counter decremented to: {}", count);
        count
    }

    /// Reset the counter to zero
    pub fn reset(env: Env) {
        env.storage().instance().set(&DataKey::Counter, &0_i64);
        log!(&env, "Counter reset to 0");
    }

    /// Get the current counter value
    pub fn get_count(env: Env) -> i64 {
        env.storage()
            .instance()
            .get(&DataKey::Counter)
            .unwrap_or(0)
    }
}