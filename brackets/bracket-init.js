import { DoubleBracketManager } from './double-bracket-manager.js';

// Export the initialization functions
export function initDoubleBracket(gameId, departmentId, gradeLevel) {
    const bracketManager = new DoubleBracketManager({
        gameId: gameId,
        departmentId: departmentId,
        gradeLevel: gradeLevel
    });

    return bracketManager;
} 