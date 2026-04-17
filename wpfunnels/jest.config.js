/**
 * Jest configuration for JS unit tests.
 * Used for CSS/DOM-based tests (e.g., shimmer loader position bug exploration).
 */
module.exports = {
    testEnvironment: 'jest-environment-jsdom',
    testMatch: ['**/tests/js/**/*.test.js'],
    transform: {},
    // No transform needed — test files use CommonJS (require/fs)
};
