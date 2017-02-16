<template>
    <div class="calendar">
        <div class="navigation">
            <a href="#" class="previous" v-show="showPrevBtn" v-on:click.prevent="previousMonth()">trug</a>
            <span class="month-name">{{ activeMonthName() }} {{ activeYear }}</span>
            <a href="#" class="next" v-show="showNextBtn" v-on:click.prevent="nextMonth()">volg</a>
        </div>
        <div class="days">
            <div>ma</div>
            <div>din</div>
            <div>woe</div>
            <div>don</div>
            <div>vri</div>
            <div>zat</div>
            <div>zon</div>
        </div>
        <div class="months">
          <div class="month" v-for="month in calendar" v-show="month.num === activeMonth && month.year === activeYear">
            <a href="#" v-on:click.prevent="dayClicked(day, (month.days.length === (index + 1)))" class="day" v-bind:class="[{selected: day.selected}, dayClass(day)]" v-for="(day, index) in month.days">
              {{ dayDisplay(day.date, day.showFullDate) }}
            </a>
          </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment'

export default {
  name: 'calendar',
  props: [
    'existingDates'
  ],
  data () {
    return {
      msg: 'Welcome to Your Vue.js App',
      now: moment(),
      calendarEl: $('.calendar'),
      previousBtn: null,
      nextBtn: null,
      isAnimating: false,
      firstIteration: true,
      year: false,
      activeMonth: false, // The month you are looking at at this point. Is defaulted to january for now
      activeYear: false,
      addedDates: [],
      calendar: []
    }
  },
  computed: {
    showPrevBtn () {
      let monthToBe = this.activeMonth
      let yearToBe = this.activeYear
      if (this.activeMonth === 0) {
        monthToBe = 11
        yearToBe = this.activeYear - 1
      } else {
        --monthToBe
      }

      return this.monthExists(monthToBe, yearToBe)
    },
    showNextBtn () {
      let monthToBe = this.activeMonth
      let yearToBe = this.activeYear

      if (this.activeMonth === 11) {
        monthToBe = 0
        yearToBe = this.activeYear + 1
      } else {
        ++monthToBe
      }

      return this.monthExists(monthToBe, yearToBe)
    }
  },
  created () {
    this.previousBtn = this.calendarEl.find('.navigation .previous')
    this.nextBtn = this.calendarEl.find('.navigation .next')

    if (this.existingDates) {
      // set the times of the existing dates to 23:59:59, same as all other dates in the calendar
      this.existingDates.forEach(existingDate => {
        let eD = moment(existingDate)
        eD.hours(23)
        eD.minutes(59)
        eD.seconds(59)

        this.addedDates.push(eD.format('x'))
      })
    }

    this.year = this.now.year()
    this.activeMonth = this.now.month()
    this.activeYear = this.year

    this.initialise()
  },
  methods: {
    dayClass (day) {
      let totalClass = ''

      if (day.inactive) {
        totalClass += 'inactive'
      }

      if (day.isPrevMonth) {
        totalClass += ' prev-month'
      }

      if (day.isNextMonth) {
        totalClass += ' next-month'
      }

      return totalClass
    },
    nextMonth () {
      let monthToBe = this.activeMonth
      let yearToBe = this.activeYear

      if (this.activeMonth === 11) {
        monthToBe = 0
        yearToBe = this.activeYear + 1
      } else {
        ++monthToBe
      }

      if (this.monthExists(monthToBe, yearToBe)) {
        this.activeMonth = monthToBe
        this.activeYear = yearToBe
      }
    },
    previousMonth () {
      let monthToBe = this.activeMonth
      let yearToBe = this.activeYear
      if (this.activeMonth === 0) {
        monthToBe = 11
        yearToBe = this.activeYear - 1
      } else {
        --monthToBe
      }

      if (this.monthExists(monthToBe, yearToBe)) {
        this.activeMonth = monthToBe
        this.activeYear = yearToBe
      }
    },
    activeMonthName () {
      return moment([this.activeYear, this.activeMonth]).locale('nl').format('MMMM')
    },
    monthExists (monthToCheck, yearToCheck) {
      let foundMatch = false
      this.calendar.forEach(month => {
        if ((month.year === yearToCheck && month.num === monthToCheck)) {
          foundMatch = true
          return
        }
      })

      return foundMatch
    },
    dayClicked (day, isLastDay) {
      if (day.inactive) {
        return
      }

      const date = moment(day.date).format('x')
      const indexOfDate = this.addedDates.findIndex(a => a.toString() === date.toString())
      let daysWithDate = this.getAllDaysWithDate(day.date)
      let selectedStatus = false
      if (indexOfDate < 0) {
        selectedStatus = true
        this.addedDates.push(date)
      } else {
        this.addedDates.splice(indexOfDate, 1)
      }

      daysWithDate.forEach(day => {
        day.selected = selectedStatus
      })

      this.$emit('newDateArray', [this.addedDates])

      if (day.isNextMonth || isLastDay) {
        this.nextMonth()
      } else if (day.isPrevMonth) {
        this.previousMonth()
      }
    },
    getAllDaysWithDate (date) {
      let days = []
      this.calendar.forEach(month => {
        month.days.forEach(day => {
          if (day.date === date) {
            days.push(day)
            return
          }
        })
      })

      return days
    },
    dayDisplay (d, showMonth = false) {
      if (showMonth) {
        return moment(d).format('DD MMM')
      }

      return moment(d).format('DD')
    },
    initialise () {
      for (let currentMonth = this.activeMonth; currentMonth < 12; currentMonth++) {
        let thisMonth = currentMonth
        if (currentMonth > 11) {
          thisMonth = currentMonth - 12

          if (thisMonth === 0) {
            ++this.year
          }
        }

        // const thisMonth = (currentMonth > 11) ? currentMonth - 12 : currentMonth;
        const month = moment([this.year, thisMonth])

        let monthObject = {
          num: thisMonth,
          name: month.format('MMMM YYYY'),
          days: [],
          year: this.year
        }

        let firstDayOfMonthNumber = ((month.date(1).weekday() - 1) + 8)
        firstDayOfMonthNumber = (firstDayOfMonthNumber > 7) ? firstDayOfMonthNumber - 7 : firstDayOfMonthNumber

        // get all the days before the first of this month to pre-fill the month (If january 1 is on a wednesday, fill monday and tuesday with 30 and 31 dec)
        const lastMonth = this.getLastMonth(this.year, thisMonth)
        const lastDayOfLastMonth = lastMonth.endOf('month').format('D')
        const daysInThisWeekFromLastMonth = (lastDayOfLastMonth - firstDayOfMonthNumber) + 2 // compensate 1 because sunday is day 0
        for (var i = daysInThisWeekFromLastMonth; i <= lastDayOfLastMonth; i++) {
          const thisDate = lastMonth.date(i)
          // set this date to the absolute last second of the day
          thisDate.hours(23)
          thisDate.minutes(59)
          thisDate.seconds(59)
          thisDate.milliseconds(59)

          let dayObject = {
            isPrevMonth: true,
            isNextMonth: false,
            date: thisDate.format(),
            inactive: false,
            showFulldate: false,
            selected: this.isSelected(thisDate)
          }

          if (thisDate < this.now) {
            // This date is in the past. Deactive it
            dayObject.inactive = true
          }

          if (parseInt(lastDayOfLastMonth) === i) {
            // last day
            dayObject.showFullDate = true
          }

          monthObject.days.push(dayObject)
        }

        let dayIterator = firstDayOfMonthNumber

        /*
            Use lastDay to define which day in this month is last.
            We use this later on to decide how many days of the next month should be appended to this month
        */
        let lastDay = false
        for (let i = 1; i <= month.daysInMonth(); i++) {
          const thisDate = moment([this.year, thisMonth]).date(i)
          // set this date to the absolute last second of the day
          thisDate.hours(23)
          thisDate.minutes(59)
          thisDate.seconds(59)
          thisDate.milliseconds(59)

          let dayObject = {
            isPrevMonth: false,
            isNextMonth: false,
            date: thisDate.format(),
            inactive: false,
            showFullDate: false,
            selected: this.isSelected(thisDate)
          }

          // let dayEl = this.createDayElement(thisDate)
          if (thisDate < this.now) {
            // This date is in the past. Deactive it
            dayObject.inactive = true
          }

          if (dayIterator === 8) {
            dayIterator = 0
          } else {
            ++dayIterator
          }

          if (month.daysInMonth() === i || i === 1) {
            // last day
            dayObject.showFullDate = true
          }

          monthObject.days.push(dayObject)

          lastDay = thisDate
        }

        if (this.firstIteration) {
          // this.calendarEl.find('.navigation .month-name').html(month.format('MMMM YYYY'))
          // this.addCalendarButtonListeners()

          this.firstIteration = false
        }

        /*
            we have saved the lastDay of the month iteration. So we can just count back from 7 to the last day in the month
            to figure out how many days from the next month should be added to this month's calendar
        */
        if (lastDay.weekday() > 0) {
          const daysToAppend = (7 - lastDay.weekday())
          for (var nextMonthDay = 1; nextMonthDay <= daysToAppend; nextMonthDay++) {
            let mo = thisMonth + 1
            let ye = this.year

            if ((thisMonth + 1) >= 12) {
              mo = 0
              ++ye
            }
            const dateForNextMonth = moment([ye, mo, nextMonthDay])
            dateForNextMonth.hours(23)
            dateForNextMonth.minutes(59)
            dateForNextMonth.seconds(59)
            dateForNextMonth.milliseconds(59)

            let dayObject = {
              isPrevMonth: true,
              isNextMonth: true,
              date: dateForNextMonth.format(),
              inactive: false,
              showFulldate: false,
              selected: this.isSelected(dateForNextMonth)
            }

            if (nextMonthDay === 1) {
              // last day
              dayObject.showFullDate = true
            }

            monthObject.days.push(dayObject)
          }
        }

        this.calendar.push(monthObject)
      }
    },
    isSelected (date) {
      let isSelected = false
      let timestamp = moment(date).format('x')

      this.existingDates.forEach(existingDate => {
        let existingDateFormat = moment(existingDate)
        existingDateFormat.hours(23)
        existingDateFormat.minutes(59)
        existingDateFormat.seconds(59)
        existingDateFormat.milliseconds(59)

        if (existingDateFormat.format('x') === timestamp) {
          isSelected = true
        }
      })

      return isSelected
    },
    getLastMonth (year, month) {
      let newMonth = month - 1

      if (newMonth < 0) {
        newMonth = 11
        --year
      }

      return moment([year, newMonth])
    }
  }
}
</script>
<style>
.calendar {
  width: 600px;
  height: 400px;
}

.calendar .days {
  height: 30px;
}

.calendar .days > div,
.calendar .month .day {
  box-sizing: border-box;
  float: left;
  width: 14.28%;
  height: 50px;
}

.calendar .months {
  border-top: 1px solid grey;
  border-left: 1px solid grey;
  position: relative;
  width: 100%;
}

.calendar .month {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  background: #FFF;
}

.calendar .day {
  border-right: 1px solid grey;
  border-bottom: 1px solid grey;
}

.calendar .prev-month,
.calendar .inactive {
  color: #ccc;
}

.calendar .prev-month {
  background: #eee;
}

.calendar .next-month {
  background: #aaa;
}

.calendar .selected {
  background-color: lightgreen;
}

.calendar .navigation {
  height: 30px;
}

.calendar .navigation a,
.calendar .navigation span {
  display: block;
  width: 25%;
  float: left;
}

.calendar .navigation span {
  width: 50%;
}
</style>
