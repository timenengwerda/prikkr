<template>
  <div v-if="mayAccess">
    <span v-if="loading">Laden</span>
    <div class="event" v-else>
      <h1>{{ event.name }}</h1>
      <a v-bind:href="editUrl" v-if="isCreator" class="btn btn-primary">Wijzig het evenement</a>
      <div v-if="everyoneVoted">
        Iedereen heeft gestemd. Hieronder van beste tot slechtste:<br>
        <ul>
          <li v-for="date in datesByScore">
            {{ formattedDate(date.timestamp) }} <br>
            Score: {{ date.score }}
          </li>
        </ul>
      </div>
      <div v-else>
        Nog niet iedereen heeft gestemd.
        <div v-if="datesByScore.length > 0 && datesByScore[0].score !== 0">
          De beste datum op dit moment zou zijn:
            <br>
            <ul>
              <li>
                {{ formattedDate(datesByScore[0].timestamp) }} <br>
                Score: {{ datesByScore[0].score }}
              </li>
            </ul>
          </div>
      </div>
      <ul>
        <li v-for="user in users">
          {{user.user.name}}
          <ul>
            <li v-for="date in user.dates">
              {{ formattedDate(date.timestamp) }} - {{ formattedChoice(date.choice.choice) }}
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
  <div v-else>
    Geen evenement ID of gebruikers ID gevonden
  </div>
</template>

<script>
import config from '../../config'
import moment from 'moment'
moment.locale('nl')

export default {
  name: 'showEvent',
  data () {
    return {
      eventId: this.$route.params.eventId,
      userId: this.$route.params.userId,
      loading: true,
      isCreator: false,
      everyoneVoted: true, // this will be set to false whenever a formattedChoice fails
      users: [{
        user: {
          name: ''
        },
        dates: {
          timestamp: ''
        }
      }],
      event: {
        id: '',
        name: '',
        description: '',
        location: '',
        creation_date: ''
      },
      groupedDates: [],
      datesByScore: []
    }
  },
  computed: {
    editUrl () {
      return `/#/event/edit/${this.eventId}/${this.userId}`
    },
    mayAccess () {
      return (this.eventId && this.userId)
    }
  },
  created () {
    this.init()
  },
  methods: {
    formattedChoice (ch) {
      if (ch === '1') {
        return 'Ja'
      }

      if (ch === '2') {
        return 'Nee'
      }

      if (ch === '3') {
        return 'Misschien'
      }

      this.everyoneVoted = false

      return 'Geen keuze'
    },
    formattedDate (date) {
      return moment(date).format('dddd DD MMMM')
    },
    init () {
      const data = {
        code: this.eventId,
        userCode: this.userId
      }

      this.$http.get(`${config.rootUrl}/api/get_event_overview.php`, data).then((result) => {
        if (result.ok && result.body.result && result.body.users && result.body.event) {
          this.users = result.body.users
          this.isCreator = result.body.is_creator
          this.event = result.body.event
          this.datesByScore = result.body.datesByScore
          this.loading = false

          // group the dates so we can easily count each of them without a user attached
          // collect all unique timestamps
          let timestamps = []
          this.users.forEach(user => {
            user.dates.forEach(date => {
              if (timestamps.indexOf(date.timestamp) === -1) {
                timestamps.push(date.timestamp)
              }
            })
          })

          // // get all data from the dates by using the uniquely found timestamps
          // let dates = []
          // if (timestamps.length) {
          //   timestamps.forEach(timestamp => {
          //     dates.push({
          //       timestamp: timestamp,
          //       yesVotes: this.getVotesOfDate(timestamp, 1),
          //       noVotes: this.getVotesOfDate(timestamp, 2),
          //       maybeVotes: this.getVotesOfDate(timestamp, 3),
          //       notVoted: this.getVotesOfDate(timestamp, 0)
          //     })
          //   })

          //   this.groupedDates = dates
          // }
        }
      }, (e) => {
        console.log(e)
      })
    },
    getVotesOfDate (timestamp, voteNum) {
      let votes = 0
      this.users.forEach(user => {
        user.dates.forEach(date => {
          if (date.timestamp === timestamp && parseInt(date.choice.choice) === voteNum) {
            ++votes
          }
        })
      })

      return votes
    }
  }
}
</script>
