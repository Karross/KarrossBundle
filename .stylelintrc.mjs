/** @type {import("stylelint").Config} */
export default {
  plugins: ['stylelint-plugin-use-baseline'],
  rules: {
    'plugin/use-baseline': [true, { available: 'widely' }],
  },
};