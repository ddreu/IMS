class BracketState {
    constructor() {
        this.observers = new Set();
        this.state = {
            teams: [],
            matches: [],
            rounds: {
                winners: 0,
                losers: 0,
                total: 0
            },
            currentBracketData: null,
            isDirty: false
        };
    }

    subscribe(observer) {
        this.observers.add(observer);
        return () => this.observers.delete(observer);
    }

    notify(changeType, data) {
        this.observers.forEach(observer => observer(changeType, data));
    }

    updateMatches(matches) {
        this.state.matches = matches;
        this.state.isDirty = true;
        this.notify('matches', matches);
    }

    updateTeams(teams) {
        this.state.teams = teams;
        this.state.isDirty = true;
        this.notify('teams', teams);
    }

    updateBracketData(bracketData) {
        this.state.currentBracketData = bracketData;
        this.state.isDirty = true;
        this.notify('bracketData', bracketData);
    }

    getState() {
        return { ...this.state };
    }
}