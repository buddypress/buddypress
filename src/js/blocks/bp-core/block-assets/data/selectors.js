/**
 * Returns the list of Active BP Components.
 *
 * @param {Object} state The current state.
 * @return {array} The list of Active BP Components.
 */
export const getActiveComponents = ( state ) => {
	return state.components || [];
};
